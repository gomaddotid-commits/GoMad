<?php

namespace App\Services;

use App\Enums\RentalStatus;
use App\Enums\RentalType;
use App\Models\CustomerDocument;
use App\Models\Rental;
use App\Models\VehicleRentalSetting;
use App\Models\Vehicle;
use App\Models\User;
use App\Models\Agency;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RentalService
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly WalletService $walletService,
    ) {}

    // ═══════════════════════════════════════════
    // VALIDASI KETERSEDIAAN
    // ═══════════════════════════════════════════

    /**
     * Cek apakah kendaraan tersedia di rentang tanggal tertentu
     */
    public function isVehicleAvailable(int $vehicleId, string $startDatetime, string $endDatetime, ?int $excludeRentalId = null): bool
    {
        $start = Carbon::parse($startDatetime);
        $end = Carbon::parse($endDatetime);

        $query = Rental::where('vehicle_id', $vehicleId)
            ->whereNotIn('status', ['cancelled'])
            ->where(function ($q) use ($start, $end) {
                $q->where('start_datetime', '<=', $end)
                  ->where('end_datetime', '>=', $start);
            });

        if ($excludeRentalId) {
            $query->where('id', '!=', $excludeRentalId);
        }

        return !$query->exists();
    }

    /**
     * Dapatkan daftar tanggal yang sudah dibooking untuk kendaraan tertentu
     */
    public function getBookedDates(int $vehicleId): array
    {
        $rentals = Rental::where('vehicle_id', $vehicleId)
            ->whereNotIn('status', ['cancelled'])
            ->where('end_datetime', '>=', now())
            ->get();

        $bookedDates = [];
        
        foreach ($rentals as $rental) {
            $start = $rental->start_datetime->startOfDay();
            $end = $rental->end_datetime->startOfDay();
            
            // Generate semua tanggal dalam rentang
            $current = $start->copy();
            while ($current->lte($end)) {
                $dateStr = $current->format('Y-m-d');
                
                if (!isset($bookedDates[$dateStr])) {
                    $bookedDates[$dateStr] = [];
                }
                
                $bookedDates[$dateStr][] = [
                    'rental_code' => $rental->rental_code,
                    'status' => $rental->status,
                    'type' => $rental->type == 'self_drive' ? 'Lepas Kunci' : 'Dengan Supir',
                ];
                
                $current->addDay();
            }
        }

        return $bookedDates;
    }

    /**
     * Dapatkan detail booking yang bentrok (untuk pesan error yang lebih informatif)
     */
    public function getConflictingRentals(int $vehicleId, string $startDatetime, string $endDatetime): Collection
    {
        $start = Carbon::parse($startDatetime);
        $end = Carbon::parse($endDatetime);

        return Rental::with('customer')
            ->where('vehicle_id', $vehicleId)
            ->whereNotIn('status', ['cancelled'])
            ->where(function ($q) use ($start, $end) {
                $q->where('start_datetime', '<=', $end)
                  ->where('end_datetime', '>=', $start);
            })
            ->get();
    }

    // ═══════════════════════════════════════════
    // AGENCY: Setup Kendaraan Rental
    // ═══════════════════════════════════════════

    public function setupVehicleForRental(Vehicle $vehicle, array $data): VehicleRentalSetting
    {
        return DB::transaction(function () use ($vehicle, $data) {
            $setting = VehicleRentalSetting::updateOrCreate(
                ['vehicle_id' => $vehicle->id],
                [
                    'is_available_for_rental' => true,
                    'description' => $data['description'] ?? null,
                    'specifications' => $data['specifications'] ?? [],
                    'price_per_hour' => $data['price_per_hour'] ?? null,
                    'price_per_day' => $data['price_per_day'] ?? null,
                    'allow_self_drive' => $data['allow_self_drive'] ?? false,
                    'allow_with_driver' => $data['allow_with_driver'] ?? true,
                    'driver_fee_per_hour' => $data['driver_fee_per_hour'] ?? null,
                    'driver_fee_per_day' => $data['driver_fee_per_day'] ?? null,
                    'requirements' => $data['requirements'] ?? ['ktp' => true, 'sim' => true],
                    'photos' => $data['photos'] ?? [],
                    'terms_conditions' => $data['terms_conditions'] ?? [],
                ]
            );

            return $setting;
        });
    }

        public function getAvailableRentalVehicles(?array $filters = []): Collection
    {
        $query = VehicleRentalSetting::with(['vehicle.agency'])
            ->where('is_available_for_rental', true)
            ->whereHas('vehicle', function ($q) {
                $q->where('is_active', true);
            })
            ->whereHas('vehicle.agency', function ($q) {
                $q->where('is_verified', true);
            });

        if (!empty($filters['type'])) {
            match ($filters['type']) {
                'self_drive' => $query->where('allow_self_drive', true),
                'with_driver' => $query->where('allow_with_driver', true),
                default => null,
            };
        }

        // ═══════════════════════════════════════
        // FILTER BY LARAVOLT LOCATION
        // ═══════════════════════════════════════
        
        // Filter by city
        if (!empty($filters['city_code'])) {
            $query->whereHas('vehicle.agency', function ($q) use ($filters) {
                $q->where('city_code', $filters['city_code']);
            });
        }

        // Filter by province
        if (!empty($filters['province_code'])) {
            $query->whereHas('vehicle.agency', function ($q) use ($filters) {
                $q->where('province_code', $filters['province_code']);
            });
        }

        // Filter by radius
        if (!empty($filters['latitude']) && !empty($filters['longitude'])) {
            $radius = $filters['radius'] ?? 50;
            $query->whereHas('vehicle.agency', function ($q) use ($filters, $radius) {
                $q->nearby(
                    (float) $filters['latitude'],
                    (float) $filters['longitude'],
                    $radius
                );
            });
        }

        if (!empty($filters['date'])) {
            $date = Carbon::parse($filters['date']);
            $query->whereDoesntHave('vehicle.rentals', function ($q) use ($date) {
                $q->whereNotIn('status', ['cancelled'])
                ->where('start_datetime', '<=', $date)
                ->where('end_datetime', '>=', $date);
            });
        }

        return $query->get();
    }

    // ═══════════════════════════════════════════
    // VALIDASI DOKUMEN CUSTOMER
    // ═══════════════════════════════════════════

    public function canCustomerUseSelfDrive(User $user): bool
    {
        $documents = CustomerDocument::where('user_id', $user->id)->first();
        
        if (!$documents) return false;
        
        return $documents->isCompleteForSelfDrive();
    }

    public function getCustomerDocumentStatus(User $user): array
    {
        $documents = CustomerDocument::where('user_id', $user->id)->first();

        return [
            'has_documents' => !is_null($documents),
            'ktp' => [
                'uploaded' => !empty($documents?->ktp_photo),
                'verified' => (bool) $documents?->ktp_verified,
                'number' => $documents?->ktp_number,
            ],
            'sim' => [
                'uploaded' => !empty($documents?->sim_photo),
                'verified' => (bool) $documents?->sim_verified,
                'number' => $documents?->sim_number,
            ],
            'npwp' => [
                'uploaded' => !empty($documents?->npwp_photo),
                'verified' => (bool) $documents?->npwp_verified,
                'number' => $documents?->npwp_number,
            ],
            'verification_status' => $documents?->verification_status ?? 'not_submitted',
            'is_complete_for_self_drive' => $documents ? $documents->isCompleteForSelfDrive() : false,
        ];
    }

    // ═══════════════════════════════════════════
    // CUSTOMER: Upload Dokumen
    // ═══════════════════════════════════════════

    public function submitDocuments(User $user, array $data): CustomerDocument
    {
        return DB::transaction(function () use ($user, $data) {
            $documents = CustomerDocument::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'ktp_number' => $data['ktp_number'] ?? null,
                    'ktp_photo' => $data['ktp_photo'] ?? null,
                    'sim_number' => $data['sim_number'] ?? null,
                    'sim_photo' => $data['sim_photo'] ?? null,
                    'npwp_number' => $data['npwp_number'] ?? null,
                    'npwp_photo' => $data['npwp_photo'] ?? null,
                    'verification_status' => 'pending',
                ]
            );

            return $documents;
        });
    }

    // ═══════════════════════════════════════════
    // CUSTOMER: Buat Booking Rental
    // ═══════════════════════════════════════════

    public function createRentalBooking(array $data): Rental
    {
        return DB::transaction(function () use ($data) {
            $customer = User::findOrFail($data['customer_id']);
            $vehicleSetting = VehicleRentalSetting::with('vehicle.agency')
                ->where('vehicle_id', $data['vehicle_id'])
                ->firstOrFail();

            $vehicle = $vehicleSetting->vehicle;
            $agency = $vehicle->agency;

            // Validasi tipe rental
            $type = RentalType::from($data['type']);
            
            if ($type === RentalType::SELF_DRIVE) {
                if (!$vehicleSetting->allow_self_drive) {
                    throw new \Exception('Mobil ini tidak tersedia untuk self-drive.');
                }
                if (!$this->canCustomerUseSelfDrive($customer)) {
                    throw new \Exception(
                        'Anda harus melengkapi verifikasi KTP & SIM terlebih dahulu. ' .
                        'Saat ini Anda hanya bisa rental dengan supir atau gunakan layanan Travel.'
                    );
                }
            }

            // Hitung durasi
            $startDateTime = Carbon::parse($data['start_datetime']);
            $endDateTime = Carbon::parse($data['end_datetime']);
            $durationUnit = $data['duration_unit'] ?? 'day';
            
            if ($durationUnit === 'hour') {
                $duration = (int) ceil($startDateTime->diffInMinutes($endDateTime) / 60);
            } else {
                $duration = (int) ceil($startDateTime->diffInDays($endDateTime));
            }

            if ($duration < 1) {
                throw new \Exception('Durasi minimal 1 ' . ($durationUnit === 'hour' ? 'jam' : 'hari') . '.');
            }

            // 👇 ========== VALIDASI KETERSEDIAAN ==========
            if (!$this->isVehicleAvailable($data['vehicle_id'], $startDateTime, $endDateTime)) {
                $conflictingRentals = $this->getConflictingRentals(
                    $data['vehicle_id'], 
                    $startDateTime, 
                    $endDateTime
                );
                
                $conflictInfo = $conflictingRentals->map(function ($r) {
                    return "• {$r->start_datetime->format('d M H:i')} - {$r->end_datetime->format('d M H:i')}";
                })->join("\n");
                
                throw new \Exception(
                    "Maaf, kendaraan ini sudah dibooking untuk rentang waktu tersebut.\n\n" .
                    "Booking yang bentrok:\n{$conflictInfo}\n\n" .
                    "Silakan pilih tanggal lain atau kendaraan lain."
                );
            }
            // 👆 ========== END VALIDASI ==========

            // Hitung harga
            $pricePerUnit = $durationUnit === 'hour' 
                ? $vehicleSetting->price_per_hour 
                : $vehicleSetting->price_per_day;

            if (!$pricePerUnit || $pricePerUnit <= 0) {
                throw new \Exception('Harga sewa belum diatur untuk kendaraan ini.');
            }

            $driverFeePerUnit = 0;
            if ($type === RentalType::WITH_DRIVER) {
                $driverFeePerUnit = $durationUnit === 'hour'
                    ? ($vehicleSetting->driver_fee_per_hour ?? 0)
                    : ($vehicleSetting->driver_fee_per_day ?? 0);
            }

            $subtotal = ($pricePerUnit + $driverFeePerUnit) * $duration;
            $platformFeeAmount = round($subtotal * 0.03);
            $totalPrice = $subtotal + $platformFeeAmount;

            // Generate rental code (unique, with retry)
            $baseCode = 'RN-' . now()->format('Ymd') . '-';
            $lastRental = Rental::where('rental_code', 'like', $baseCode . '%')
                ->orderBy('rental_code', 'desc')
                ->first();

            if ($lastRental) {
                $lastNumber = (int) substr($lastRental->rental_code, -4);
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }

            $rentalCode = $baseCode . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            // Ensure uniqueness (in case of race conditions)
            while (Rental::where('rental_code', $rentalCode)->exists()) {
                $nextNumber++;
                $rentalCode = $baseCode . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            }

            $rental = Rental::create([
                'rental_code' => $rentalCode,
                'agency_id' => $agency->id,
                'vehicle_id' => $vehicle->id,
                'customer_id' => $customer->id,
                'type' => $type->value,
                'start_datetime' => $startDateTime,
                'end_datetime' => $endDateTime,
                'duration' => $duration,
                'duration_unit' => $durationUnit,
                'price_per_unit' => $pricePerUnit,
                'driver_fee_per_unit' => $driverFeePerUnit,
                'subtotal' => $subtotal,
                'platform_fee' => $platformFeeAmount,
                'total_price' => $totalPrice,
                'discount_amount' => 0,
                'status' => RentalStatus::PENDING->value,
                'notes' => $data['notes'] ?? null,
                'pickup_address' => $data['pickup_address'] ?? null,
                'destination_address' => $data['destination_address'] ?? null,
                'pickup_maps_link' => $data['pickup_maps_link'] ?? null,
                'destination_maps_link' => $data['destination_maps_link'] ?? null,
                'status' => RentalStatus::PENDING->value,
                'notes' => $data['notes'] ?? null,
            ]);

            // 👇 ========== PROSES PROMO ==========
            $finalPrice = $totalPrice;
            $discountAmount = 0;
            
            if (!empty($data['promo_id'])) {
                $promo = \App\Models\Promo::find($data['promo_id']);
                
                if ($promo && $promo->isActiveNow() && $promo->isForModule('rental')) {
                    $promoService = app(\App\Services\PromoService::class);
                    
                    if ($promoService->canUsePromo($customer, $promo)) {
                        $discountAmount = $promoService->calculateRentalDiscount($promo, $subtotal);
                        
                        if ($discountAmount > 0) {
                            $finalPrice = max(0, $totalPrice - $discountAmount);
                            
                            $rental->update([
                                'total_price' => $finalPrice,
                                'discount_amount' => $discountAmount,
                                'promo_id' => $promo->id,
                            ]);

                            \App\Models\PromoUsage::create([
                                'promo_id' => $promo->id,
                                'user_id' => $customer->id,
                                'rental_id' => $rental->id,
                                'discount_amount' => $discountAmount,
                            ]);
                        }
                    }
                }
            }
            // 👆 ========== END PROMO ==========

            // Notifikasi
            $this->notificationService->sendWhatsApp(
                $customer->phone,
                "✅ *Booking Rental Berhasil!*\n\n" .
                "Kode: *{$rental->rental_code}*\n" .
                "Mobil: {$vehicle->brand} {$vehicle->model} ({$vehicle->plate_number})\n" .
                "Tipe: {$type->label()}\n" .
                "Durasi: {$duration} {$durationUnit}\n" .
                "Total: Rp " . number_format($finalPrice, 0, ',', '.') . "\n\n" .
                "Silakan lakukan pembayaran."
            );

            // Notifikasi agency
            if ($agency->user && $agency->user->phone) {
                $this->notificationService->sendWhatsApp(
                    $agency->user->phone,
                    "🔔 *Booking Rental Baru!*\n\n" .
                    "Kode: *{$rental->rental_code}*\n" .
                    "Customer: {$customer->name}\n" .
                    "Mobil: {$vehicle->plate_number}\n" .
                    "Tipe: {$type->label()}\n" .
                    "Durasi: {$duration} {$durationUnit}\n" .
                    "Total: Rp " . number_format($finalPrice, 0, ',', '.')
                );
            }

            return $rental->load(['vehicle', 'agency', 'customer']);
        });
    }

    public function assignDriver(Rental $rental, User $driver): Rental
    {
        if ($rental->type !== 'with_driver') {
            throw new \Exception('Hanya rental dengan supir yang bisa menugaskan supir.');
        }

        if ($driver->role !== 'driver') {
            throw new \Exception('User bukan driver.');
        }

        if ($driver->agency_id !== $rental->agency_id) {
            throw new \Exception('Driver harus dari agency yang sama.');
        }

        $rental->update(['driver_id' => $driver->id]);

        // Notifikasi ke customer
        if ($rental->customer->phone) {
            $this->notificationService->sendWhatsApp(
                $rental->customer->phone,
                "👨‍✈️ *Supir Telah Ditugaskan!*\n\n" .
                "Kode Rental: *{$rental->rental_code}*\n" .
                "Supir: *{$driver->name}*\n" .
                "Telepon: *{$driver->phone}*\n" .
                "Mobil: {$rental->vehicle->plate_number}\n\n" .
                "Supir akan menjemput Anda di:\n" .
                "{$rental->pickup_address}\n\n" .
                "Silakan hubungi supir untuk koordinasi."
            );
        }

        // Notifikasi ke driver
        if ($driver->phone) {
            $this->notificationService->sendWhatsApp(
                $driver->phone,
                "🔔 *Tugas Rental Baru!*\n\n" .
                "Kode: *{$rental->rental_code}*\n" .
                "Customer: *{$rental->customer->name}*\n" .
                "Telepon Customer: *{$rental->customer->phone}*\n" .
                "Mobil: {$rental->vehicle->plate_number}\n" .
                "Jemput di: {$rental->pickup_address}\n" .
                "Tanggal: {$rental->start_datetime->format('d M Y H:i')}\n\n" .
                "Cek aplikasi untuk detail."
            );
        }

        return $rental;
    }


    // ═══════════════════════════════════════════
    // AGENCY: Verifikasi Pengambilan
    // ═══════════════════════════════════════════

    public function verifyPickup(Rental $rental): Rental
    {
        return DB::transaction(function () use ($rental) {
            if ($rental->status !== RentalStatus::PAID->value) {
                throw new \Exception('Rental harus dalam status Siap Diambil.');
            }

            $rental->update([
                'status' => RentalStatus::ACTIVE->value,
                'started_at' => now(),
            ]);

            if ($rental->customer->phone) {
                $this->notificationService->sendWhatsApp(
                    $rental->customer->phone,
                    "🚗 *Mobil Sudah Diambil!*\n\n" .
                    "Kode: *{$rental->rental_code}*\n" .
                    "Mobil: {$rental->vehicle->plate_number}\n" .
                    "Sampai: {$rental->end_datetime->format('d M Y H:i')}\n\n" .
                    "Selamat berkendara!"
                );
            }

            return $rental;
        });
    }

    // ═══════════════════════════════════════════
    // AGENCY: Verifikasi Pengembalian
    // ═══════════════════════════════════════════

    public function verifyReturn(Rental $rental): Rental
    {
        return DB::transaction(function () use ($rental) {
            if ($rental->status !== RentalStatus::ACTIVE->value) {
                throw new \Exception('Rental harus dalam status Sedang Disewa.');
            }

            $rental->update([
                'status' => RentalStatus::RETURNED->value,
                'returned_at' => now(),
            ]);

            if ($rental->customer->phone) {
                $this->notificationService->sendWhatsApp(
                    $rental->customer->phone,
                    "✅ *Mobil Sudah Dikembalikan!*\n\n" .
                    "Kode: *{$rental->rental_code}*\n" .
                    "Terima kasih telah menggunakan layanan GoMad Rental."
                );
            }

            return $rental;
        });
    }

    // ═══════════════════════════════════════════
    // COMPLETE Rental
    // ═══════════════════════════════════════════

    public function completeRental(Rental $rental): Rental
    {
        if ($rental->status !== RentalStatus::RETURNED->value) {
            throw new \Exception('Rental harus dalam status Menunggu Verifikasi.');
        }

        $rental->update(['status' => RentalStatus::COMPLETED->value]);
        
        $revenue = $rental->subtotal - $rental->platform_fee;
        $this->walletService->creditWallet(
            $rental->agency,
            $revenue,
            "Pendapatan rental {$rental->rental_code}",
            'rental_revenue',
            $rental->id
        );

        return $rental;
    }

    // ═══════════════════════════════════════════
    // GETTERS
    // ═══════════════════════════════════════════

    public function getCustomerRentals(User $user, ?string $status = null): Collection
    {
        $query = Rental::with(['vehicle.rentalSetting', 'agency', 'promo'])
            ->where('customer_id', $user->id)
            ->latest();

        if ($status) {
            $query->where('status', $status);
        }

        return $query->get();
    }

    public function getAgencyRentals(Agency $agency, ?string $status = null): Collection
    {
        $query = Rental::with(['vehicle', 'customer'])
            ->where('agency_id', $agency->id)
            ->latest();

        if ($status) {
            $query->where('status', $status);
        }

        return $query->get();
    }

    /**
     * Batalkan rental + proses refund
     */
    public function cancelRental(Rental $rental): Rental
    {
        return DB::transaction(function () use ($rental) {
            // Validasi status
            if (!in_array($rental->status, ['pending', 'paid'])) {
                throw new \Exception('Rental tidak dapat dibatalkan pada status ini.');
            }

            // Cek apakah sudah lewat tanggal mulai
            if ($rental->start_datetime->isPast() && $rental->status == 'paid') {
                throw new \Exception('Rental sudah dimulai, tidak dapat dibatalkan.');
            }

            $oldStatus = $rental->status;
            $cancellationFee = 0;
            $refundAmount = 0;

            // Hitung biaya pembatalan (25% jika sudah paid)
            if ($oldStatus === 'paid' && $rental->payment) {
                $cancellationFee = round($rental->total_price * 0.25);
                $refundAmount = $rental->total_price - $cancellationFee;
            }

            // Update status rental
            $rental->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            // Proses refund jika ada payment
            if ($rental->payment) {
                $paymentService = app(\App\Services\PaymentService::class);

                if ($rental->payment->payment_type === 'midtrans' && $rental->payment->status === 'paid') {
                    // Refund via Midtrans
                    $refundResult = $paymentService->refundPaymentForRental($rental, $refundAmount);
                    
                    \Log::info('Rental refund processed', [
                        'rental_code' => $rental->rental_code,
                        'total_price' => $rental->total_price,
                        'cancellation_fee' => $cancellationFee,
                        'refund_amount' => $refundAmount,
                        'result' => $refundResult,
                    ]);
                } elseif ($rental->payment->payment_type === 'cash' && $rental->payment->status === 'paid') {
                    // Cash payment: update status jadi refund_pending
                    $rental->payment->update(['status' => 'refund_pending']);
                    
                    if ($rental->cashPayment) {
                        $rental->cashPayment->update(['status' => 'refund_pending']);
                    }
                    
                    \Log::info('Rental cash refund pending', [
                        'rental_code' => $rental->rental_code,
                    ]);
                } else {
                    // Belum paid, expire payment
                    $rental->payment->update(['status' => \App\Enums\PaymentStatus::EXPIRED->value]);
                    
                    if ($rental->cashPayment) {
                        $rental->cashPayment->update(['status' => 'expired']);
                    }
                }

                // Kurangi pending_balance agency jika sudah paid
                if ($oldStatus === 'paid' && (float) $rental->payment->agency_revenue > 0) {
                    $agency = $rental->agency;
                    $walletService = app(\App\Services\WalletService::class);
                    $wallet = $walletService->getOrCreateWallet($agency);
                    $wallet->update([
                        'pending_balance' => max(0, (float) $wallet->pending_balance - (float) $rental->payment->agency_revenue),
                    ]);
                }
            }

            // Notifikasi customer
            if ($rental->customer->phone) {
                $message = "❌ *Rental Dibatalkan*\n\n" .
                    "Kode: *{$rental->rental_code}*\n" .
                    "Mobil: {$rental->vehicle->plate_number}\n\n";
                
                if ($refundAmount > 0) {
                    $message .= "Biaya pembatalan: Rp " . number_format($cancellationFee, 0, ',', '.') . " (25%)\n" .
                            "Dana dikembalikan: Rp " . number_format($refundAmount, 0, ',', '.');
                } else {
                    $message .= "Tidak ada biaya pembatalan.";
                }
                
                $this->notificationService->sendWhatsApp($rental->customer->phone, $message);
            }

            // Notifikasi agency
            if ($rental->agency->user && $rental->agency->user->phone) {
                $this->notificationService->sendWhatsApp(
                    $rental->agency->user->phone,
                    "❌ *Rental Dibatalkan*\n\n" .
                    "Kode: *{$rental->rental_code}*\n" .
                    "Customer: {$rental->customer->name}\n" .
                    "Mobil: {$rental->vehicle->plate_number}\n" .
                    "Status: " . ($refundAmount > 0 ? "Refund Rp " . number_format($refundAmount, 0, ',', '.') : "Tidak ada refund")
                );
            }

            return $rental->fresh();
        });
    }

    // Di dalam method getAvailableRentalVehicles(), tambahkan:


}