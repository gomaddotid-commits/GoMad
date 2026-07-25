<?php
// File: app/Services/BookingService.php
// Deskripsi: Service untuk business logic booking

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\TravelClass;
use App\Exceptions\InvalidRouteStopException;
use App\Exceptions\ScheduleFullException;
use App\Helpers\BookingCodeGenerator;
use App\Models\Booking;
use App\Models\BookingPassenger;
use App\Models\RouteStop;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class BookingService
{
    public function __construct(
        private readonly OverloadService $overloadService,
        private readonly PricingService $pricingService,
        private readonly NotificationService $notificationService,
    ) {}

    public function createBooking(array $data): Booking
    {
        return DB::transaction(function () use ($data) {
            // ⚡ PESSIMISTIC LOCK pada schedule untuk mencegah overbooking
            $schedule = Schedule::with(['route.stops', 'scheduleStops'])
                ->where('id', $data['schedule_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $customer = User::findOrFail($data['customer_id']);

            $this->validateBooking($data, $schedule);

            $originStop = RouteStop::findOrFail($data['origin_stop_id']);
            $destinationStop = RouteStop::findOrFail($data['destination_stop_id']);

            $pricing = $this->pricingService->getRoutePricing($schedule, $originStop, $destinationStop);

            if (!$pricing) {
                throw new InvalidRouteStopException('Harga untuk kombinasi rute ini belum tersedia.');
            }

            // ⚡ DOUBLE-CHECK KAPASITAS SETELAH LOCK
            $currentBookedAfterLock = (int) Booking::where('schedule_id', $schedule->id)
                ->whereNotIn('status', ['cancelled'])
                ->sum('total_passengers');

            $maxCapacity = $this->overloadService->getMaxCapacity($schedule);
            $requestedSeats = count($data['passengers']);

            if (($currentBookedAfterLock + $requestedSeats) > $maxCapacity) {
                throw new ScheduleFullException(
                    "Jadwal penuh setelah verifikasi akhir. " .
                    "Tersedia: " . ($maxCapacity - $currentBookedAfterLock) .
                    " kursi, diminta: {$requestedSeats} kursi."
                );
            }

            $basePrice = $pricing->price * count($data['passengers']);

            $serviceFee = (float) \App\Models\PlatformSetting::getValue('service_fee', 5000);
            $platformFeePercent = (float) \App\Models\PlatformSetting::getValue('platform_fee_percent', 3);
            $platformFee = $basePrice * ($platformFeePercent / 100);

            $subtotal = $basePrice + $serviceFee + $platformFee;

            $discountAmount = 0;
            $promoId = null;

            if (!empty($data['promo_id'])) {
                $promoService = app(\App\Services\PromoService::class);
                $promo = \App\Models\Promo::find($data['promo_id']);

                if ($promo && $promo->isActiveNow()) {
                    $canUsePromo = $promoService->canUsePromo($customer, $promo);

                    if ($canUsePromo) {
                        $isValidPromo = false;

                        if ($promo->type === 'general' || $promo->type === 'referral') {
                            $isValidPromo = true;
                        } elseif ($promo->type === 'selective') {
                            $isValidPromo = $schedule->promos()->where('promo_id', $promo->id)->exists();
                        }

                        if ($isValidPromo) {
                            $discountAmount = min(
                                $basePrice * ($promo->discount_percent / 100),
                                (float) $promo->max_discount
                            );
                            if ($discountAmount > 0) {
                                $promoId = $promo->id;
                            }
                        }
                    }
                }
            }

            $totalPrice = $pricing->price * count($data['passengers']);
            $totalPrice += $serviceFee + $platformFee;
            $finalPrice = max(0, $totalPrice - $discountAmount);

            $bookingCode = BookingCodeGenerator::generate($schedule->id);

            $booking = Booking::create([
                'booking_code' => $bookingCode,
                'schedule_id' => $schedule->id,
                'customer_id' => $customer->id,
                'origin_stop_id' => $originStop->id,
                'destination_stop_id' => $destinationStop->id,
                'route_pricing_id' => $pricing->id,
                'pickup_address' => $data['pickup_address'],
                'pickup_maps_link' => $data['pickup_maps_link'] ?? null,
                'pickup_latitude' => $data['pickup_latitude'] ?? null,
                'pickup_longitude' => $data['pickup_longitude'] ?? null,
                'destination_address' => $data['destination_address'],
                'destination_maps_link' => $data['destination_maps_link'] ?? null,
                'destination_latitude' => $data['destination_latitude'] ?? null,
                'destination_longitude' => $data['destination_longitude'] ?? null,
                'total_passengers' => count($data['passengers']),
                'total_price' => $finalPrice,
                'base_price' => $basePrice,
                'service_fee' => $serviceFee,
                'platform_fee' => $platformFee,
                'discount_amount' => $discountAmount,
                'status' => BookingStatus::PENDING->value,
                'special_notes' => $data['special_notes'] ?? null,
            ]);

            if ($promoId && $discountAmount > 0) {
                \App\Models\PromoUsage::create([
                    'promo_id' => $promoId,
                    'user_id' => $customer->id,
                    'booking_id' => $booking->id,
                    'discount_amount' => $discountAmount,
                ]);
            }

            foreach ($data['passengers'] as $index => $passenger) {
                BookingPassenger::create([
                    'booking_id' => $booking->id,
                    'passenger_name' => $passenger['name'],
                    'passenger_phone' => $passenger['phone'] ?? null,
                    'baggage_weight' => $passenger['baggage_weight'] ?? 0,
                    'seat_number' => $index + 1,
                ]);
            }

            // ═══════════════════════════════════════════
            // NOTIFIKASI: Via dispatchAfterResponse (ASYNC)
            // ═══════════════════════════════════════════

            // In-app notification untuk agency (sync — ringan)
            $agency = $booking->schedule->agency;
            if ($agency && $agency->user) {
                $this->notificationService->createNotification(
                    $agency->user->id,
                    '📋 Booking Baru',
                    "Booking {$booking->booking_code} dari {$booking->customer->name} - " .
                    "{$booking->originStop->city_name} → {$booking->destinationStop->city_name} - " .
                    "Rp " . number_format($booking->total_price, 0, ',', '.') . " - Status: Menunggu Pembayaran",
                    ['type' => 'new_booking', 'booking_id' => $booking->id, 'booking_code' => $booking->booking_code]
                );
            }

            // WhatsApp notifications via dispatchAfterResponse (ASYNC — tidak blocking)
            dispatch(function () use ($booking) {
                $this->notificationService->bookingCreated($booking);
            })->afterResponse();

            return $booking->load(['passengers', 'schedule', 'originStop', 'destinationStop']);
        });
    }


    public function validateBooking(array $data, Schedule $schedule): void
    {
        if (!$schedule->is_active) {
            throw new \Exception('Jadwal tidak tersedia.');
        }

        $departureDateTime = \Carbon\Carbon::parse(
            $schedule->departure_date->format('Y-m-d') . ' ' . $schedule->departure_time
        );
        
        if ($departureDateTime->isPast()) {
            throw new \Exception('Jadwal sudah berangkat. Tidak dapat melakukan booking.');
        }

        $originStop = RouteStop::findOrFail($data['origin_stop_id']);
        $destinationStop = RouteStop::findOrFail($data['destination_stop_id']);

        if ($originStop->route_id !== $schedule->route_id || $destinationStop->route_id !== $schedule->route_id) {
            throw new InvalidRouteStopException('Stop tidak sesuai dengan rute jadwal.');
        }

        if ($originStop->stop_order >= $destinationStop->stop_order) {
            throw new InvalidRouteStopException('Stop asal harus sebelum stop tujuan.');
        }

        $routeStops = $schedule->route->stops;
        $firstStop = $routeStops->first();
        $lastStop = $routeStops->last();

        if ($originStop->stop_order === $lastStop->stop_order) {
            throw new InvalidRouteStopException('Stop terakhir hanya untuk drop-off, tidak bisa pickup.');
        }

        if ($destinationStop->stop_order === $firstStop->stop_order) {
            throw new InvalidRouteStopException('Stop pertama hanya untuk pickup, tidak bisa drop-off.');
        }

        $requestedSeats = count($data['passengers'] ?? []);
        if (!$this->overloadService->validateCapacity($schedule, $requestedSeats)) {
            throw new ScheduleFullException('Jadwal sudah penuh. Sisa kursi: ' . $this->overloadService->getCurrentBookedSeats($schedule));
        }

        $totalBaggage = collect($data['passengers'] ?? [])->sum(function ($p) {
            return $p['baggage_weight'] ?? 0;
        });
        $avgBaggage = count($data['passengers'] ?? []) > 0 ? $totalBaggage / count($data['passengers']) : 0;
        if ($avgBaggage > $schedule->baggage_limit_kg) {
            throw new \Exception("Rata-rata bagasi ({$avgBaggage}kg) melebihi batas ({$schedule->baggage_limit_kg}kg/orang).");
        }
    }

    public function cancelBooking(Booking $booking): bool
    {
        return DB::transaction(function () use ($booking) {
            // Validasi
            if (!$booking->schedule) {
                throw new \Exception('Data jadwal booking tidak ditemukan.');
            }

            if ($booking->status === BookingStatus::CANCELLED->value) {
                throw new \Exception('Booking sudah dibatalkan sebelumnya.');
            }

            if ($booking->status === BookingStatus::COMPLETED->value) {
                throw new \Exception('Booking sudah selesai, tidak dapat dibatalkan.');
            }

            if (!$booking->can_cancel) {
                if ($booking->status === 'paid') {
                    $departureDateTime = \Carbon\Carbon::parse(
                        $booking->schedule->departure_date->format('Y-m-d') . ' ' . $booking->schedule->departure_time
                    );
                    $hoursUntilDeparture = now()->diffInHours($departureDateTime, false);

                    if ($hoursUntilDeparture <= 24) {
                        throw new \Exception(
                            'Booking tidak dapat dibatalkan karena kurang dari 24 jam sebelum keberangkatan. ' .
                            'Hubungi agency untuk bantuan.'
                        );
                    }
                }

                throw new \Exception('Booking tidak dapat dibatalkan pada status: ' . $booking->status_label);
            }

            $oldStatus = $booking->status;
            $oldPaymentStatus = $booking->payment ? $booking->payment->status : null;

            $booking->update([
                'status' => BookingStatus::CANCELLED->value,
                'cancelled_at' => now(),
            ]);

            if ($booking->payment) {
                $paymentService = app(\App\Services\PaymentService::class);
                $walletService = app(\App\Services\WalletService::class);

                switch ($booking->payment->payment_type) {
                    case 'midtrans':
                        if ($oldPaymentStatus === PaymentStatus::PAID->value) {
                            $refundAmount = $booking->cancellation_refund;
                            if ($refundAmount > 0) {
                                $paymentService->refundPayment($booking, $refundAmount);
                            }
                        } else {
                            $booking->payment->update(['status' => PaymentStatus::EXPIRED->value]);
                        }
                        break;

                    case 'cash':
                        if ($booking->cashPayment) {
                            if ($booking->cashPayment->status === 'confirmed') {
                                $booking->cashPayment->update(['status' => 'refund_pending']);
                            } else {
                                $booking->cashPayment->update(['status' => 'expired']);
                            }
                        }
                        break;

                    case 'cod':
                        if ($oldPaymentStatus === PaymentStatus::COD_PENDING->value) {
                            $walletService->releaseCodBalance($booking);
                            $booking->payment->update(['status' => PaymentStatus::EXPIRED->value]);
                        } elseif ($oldPaymentStatus === PaymentStatus::COD_CONFIRMED->value) {
                            $booking->update([
                                'status' => $oldStatus,
                                'cancelled_at' => null,
                            ]);
                            throw new \Exception(
                                'Booking dengan pembayaran COD yang sudah dikonfirmasi tidak dapat dibatalkan. ' .
                                'Hubungi agency untuk bantuan.'
                            );
                        }
                        break;
                }

                if ($oldPaymentStatus === PaymentStatus::PAID->value) {
                    $agency = $booking->schedule->agency;
                    if ($agency && (float) $booking->payment->agency_revenue > 0) {
                        $wallet = $walletService->getOrCreateWallet($agency);
                        $wallet->update([
                            'pending_balance' => max(0, (float) $wallet->pending_balance - (float) $booking->payment->agency_revenue),
                        ]);
                    }
                }
            } else {
                if ($booking->cashPayment) {
                    $booking->cashPayment->update(['status' => 'expired']);
                }
            }

            // ═══════════════════════════════════════════
            // NOTIFIKASI via dispatchAfterResponse (ASYNC)
            // ═══════════════════════════════════════════
            dispatch(function () use ($booking) {
                $this->notificationService->bookingCancelled($booking, 'Dibatalkan oleh customer');
            })->afterResponse();

            Log::info('Booking cancelled successfully', [
                'booking_code' => $booking->booking_code,
                'old_status' => $oldStatus,
                'payment_type' => $booking->payment->payment_type ?? 'none',
                'old_payment_status' => $oldPaymentStatus,
            ]);

            return true;
        });
    }

    public function completeBooking(Booking $booking): bool
    {
        return DB::transaction(function () use ($booking) {
            if ($booking->status !== BookingStatus::ON_GOING->value) {
                throw new \Exception('Booking harus dalam status On Going untuk diselesaikan.');
            }

            $booking->update([
                'status' => BookingStatus::COMPLETED->value,
                'completed_at' => now(),
            ]);

            // UPDATE AGENCY COUNTER
            $agency = $booking->schedule->agency;
            $agency->increment('total_bookings');
            
            // UPDATE AGENCY RATING
            $avgRating = \App\Models\Review::where('agency_id', $agency->id)->avg('rating') ?? 0;
            $agency->update(['rating' => round($avgRating, 2)]);

            $this->notificationService->bookingCompleted($booking);

            return true;
        });
    }

    public function getCustomerBookings(User $user, ?string $status = null): Collection
    {
        $query = Booking::with(['schedule.route', 'originStop', 'destinationStop', 'payment', 'passengers'])
            ->where('customer_id', $user->id)
            ->latest();

        if ($status) {
            $query->byStatus($status);
        }

        return $query->get();
    }

    public function getAgencyBookings(int $agencyId, ?array $filters = []): Collection
    {
        $query = Booking::with(['schedule', 'customer', 'originStop', 'destinationStop', 'payment', 'passengers'])
            ->whereHas('schedule', function ($q) use ($agencyId) {
                $q->where('agency_id', $agencyId);
            })
            ->latest();

        if (!empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (!empty($filters['schedule_id'])) {
            $query->where('schedule_id', $filters['schedule_id']);
        }

        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $query->whereHas('schedule', function ($q) use ($filters) {
                $q->whereBetween('departure_date', [$filters['date_from'], $filters['date_to']]);
            });
        }

        return $query->get();
    }
}

// End of file