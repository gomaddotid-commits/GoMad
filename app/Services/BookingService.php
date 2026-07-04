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
            $schedule = Schedule::with(['route.stops', 'scheduleStops'])->findOrFail($data['schedule_id']);
            $customer = User::findOrFail($data['customer_id']);

            $this->validateBooking($data, $schedule);

            $originStop = RouteStop::findOrFail($data['origin_stop_id']);
            $destinationStop = RouteStop::findOrFail($data['destination_stop_id']);

            $pricing = $this->pricingService->getRoutePricing($schedule, $originStop, $destinationStop);

            if (!$pricing) {
                throw new InvalidRouteStopException('Harga untuk kombinasi rute ini belum tersedia.');
            }

            
            $basePrice = $pricing->price * count($data['passengers']);
            
            // Hitung biaya
            $serviceFee = (float) \App\Models\PlatformSetting::getValue('service_fee', 5000);
            $platformFeePercent = (float) \App\Models\PlatformSetting::getValue('platform_fee_percent', 3);
            $platformFee = $basePrice * ($platformFeePercent / 100);
            
            // Subtotal sebelum diskon
            $subtotal = $basePrice + $serviceFee + $platformFee;
            
            // Proses promo
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
                            // Diskon hanya dari base price (sebelum biaya)
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
            
            // Total akhir
            $totalPrice = $pricing->price * count($data['passengers']);
            // Tambahkan biaya layanan dan platform
            $totalPrice += $serviceFee + $platformFee;
            // Kurangi diskon
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

            // Catat penggunaan promo
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

            $this->notificationService->bookingCreated($booking);

            $agency = $booking->schedule->agency;
            if ($agency && $agency->user) {
                $this->notificationService->createNotification(
                    $agency->user->id,
                    '📋 Booking Baru',
                    "Booking {$booking->booking_code} dari {$booking->customer->name} - " .
                    "{$booking->originStop->city_name} → {$booking->destinationStop->city_name} - " .
                    "Rp " . number_format($booking->total_price, 0, ',', '.') . " - Status: Menunggu Pembayaran",
                    [
                        'type' => 'new_booking',
                        'booking_id' => $booking->id,
                        'booking_code' => $booking->booking_code,
                    ]
                );
                
                // Kirim WhatsApp ke agency
                $this->notificationService->sendWhatsApp(
                    $agency->user->phone,
                    "📋 *Booking Baru!*\n\n" .
                    "Kode: *{$booking->booking_code}*\n" .
                    "Customer: {$booking->customer->name}\n" .
                    "Rute: {$booking->originStop->city_name} → {$booking->destinationStop->city_name}\n" .
                    "Tanggal: {$booking->schedule->departure_date->format('d M Y')} {$booking->schedule->departure_time}\n" .
                    "Penumpang: {$booking->total_passengers} orang\n" .
                    "Total: Rp " . number_format($booking->total_price, 0, ',', '.') . "\n" .
                    "Status: *Menunggu Pembayaran*\n\n" .
                    "Cek dashboard untuk detail."
                );
            }
            // ========================================

            return $booking->load(['passengers', 'schedule', 'originStop', 'destinationStop']);
        });
    }


    public function validateBooking(array $data, Schedule $schedule): void
    {
        if (!$schedule->is_active) {
            throw new \Exception('Jadwal tidak tersedia.');
        }

        // 👇 UBAH: Gunakan kombinasi departure_date + departure_time
        $departureDateTime = \Carbon\Carbon::parse(
            $schedule->departure_date->format('Y-m-d') . ' ' . $schedule->departure_time
        );
        
        if ($departureDateTime->isPast()) {
            throw new \Exception('Jadwal sudah berangkat. Tidak dapat melakukan booking.');
        }

        if ($schedule->travel_class === TravelClass::RENTAL->value) {
            throw new \Exception('Kelas rental tidak bisa dibooking melalui sistem.');
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
            if (!$booking->can_cancel) {
                // Cek kenapa tidak bisa cancel
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
                
                throw new \Exception('Booking tidak dapat dibatalkan pada status ini.');
            }

            $oldStatus = $booking->status;

            $booking->update([
                'status' => BookingStatus::CANCELLED->value,
                'cancelled_at' => now(),
            ]);

            // Jika sudah paid, proses refund
            if ($oldStatus === BookingStatus::PAID->value && $booking->payment) {
                $paymentService = app(\App\Services\PaymentService::class);
                
                if ($booking->payment->payment_type === 'midtrans') {
                    // Refund via Midtrans API
                    $refundAmount = $booking->cancellation_refund; // 75% dari total
                    
                    $result = $paymentService->refundPayment($booking, $refundAmount);
                    
                    \Log::info('Cancel booking with refund', [
                        'booking_code' => $booking->booking_code,
                        'total_price' => $booking->total_price,
                        'cancellation_fee' => $booking->cancellation_fee,
                        'refund_amount' => $refundAmount,
                        'refund_result' => $result,
                    ]);
                } elseif ($booking->payment->payment_type === 'cash' && $booking->cashPayment) {
                    // Cash payment: tidak ada refund otomatis
                    if ($booking->cashPayment->status === 'confirmed') {
                        // Hanya update status, customer harus ke warung untuk refund
                        $booking->cashPayment->update(['status' => 'refund_pending']);
                        
                        \Log::info('Cash refund pending - customer must visit warung', [
                            'booking_code' => $booking->booking_code,
                        ]);
                    } else {
                        $booking->cashPayment->update(['status' => 'expired']);
                    }
                } elseif ($booking->payment->payment_type === 'cod') {
                    // COD: release hold balance
                    app(\App\Services\WalletService::class)->releaseCodBalance($booking);
                    $booking->payment->update(['status' => PaymentStatus::EXPIRED->value]);
                }
                
                // Kurangi pending_balance agency
                $agency = $booking->schedule->agency;
                if ($agency && (float) $booking->payment->agency_revenue > 0) {
                    $walletService = app(\App\Services\WalletService::class);
                    $wallet = $walletService->getOrCreateWallet($agency);
                    $wallet->update([
                        'pending_balance' => max(0, (float) $wallet->pending_balance - (float) $booking->payment->agency_revenue),
                    ]);
                }
            } else {
                // Pending/confirmed — tidak ada refund
                if ($booking->cashPayment) {
                    $booking->cashPayment->update(['status' => 'expired']);
                }
                if ($booking->payment && $booking->payment->payment_type === 'cod') {
                    $booking->payment->update(['status' => PaymentStatus::EXPIRED->value]);
                    app(\App\Services\WalletService::class)->releaseCodBalance($booking);
                }
            }

            $this->notificationService->bookingCancelled(
                $booking, 
                'Dibatalkan oleh customer'
            );


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