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
use App\Models\Schedule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
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
     * + cek bentrok dengan jadwal travel + masa istirahat
     */
    public function isVehicleAvailable(int $vehicleId, string $startDatetime, string $endDatetime, ?int $excludeRentalId = null): bool
    {
        $start = Carbon::parse($startDatetime);
        $end = Carbon::parse($endDatetime);

        // 1. Cek bentrok dengan rental lain
        $rentalConflict = Rental::where('vehicle_id', $vehicleId)
            ->whereNotIn('status', ['cancelled'])
            ->where(function ($q) use ($start, $end) {
                $q->where('start_datetime', '<=', $end)
                  ->where('end_datetime', '>=', $start);
            });

        if ($excludeRentalId) {
            $rentalConflict->where('id', '!=', $excludeRentalId);
        }

        if ($rentalConflict->exists()) {
            return false;
        }

        // 2. ✅ Cek bentrok dengan jadwal travel + masa istirahat
        // Travel di rentang tersebut
        $scheduleConflict = Schedule::where('vehicle_id', $vehicleId)
            ->where('is_active', true)
            ->where(function ($q) use ($start, $end) {
                // Jadwal travel di rentang tanggal
                $q->where(function ($sq) use ($start, $end) {
                    $sq->where('departure_date', '>=', $start->toDateString())
                       ->where('departure_date', '<=', $end->toDateString());
                })
                // Atau masa istirahat yang mencakup rentang tersebut
                ->orWhere(function ($sq) use ($start, $end) {
                    $sq->whereNotNull('available_for_rental_after')
                       ->where('available_for_rental_after', '>=', $start->toDateString())
                       ->where('departure_date', '<=', $end->toDateString());
                });
            })
            ->exists();

        if ($scheduleConflict) {
            return false;
        }

        return true;
    }

    /**
     * Dapatkan daftar tanggal yang sudah dibooking untuk kendaraan tertentu
     * (termasuk rental + travel + masa istirahat)
     */
    public function getBookedDates(int $vehicleId): array
    {
        $bookedDates = [];

        // 1. Cek booking rental
        $rentals = Rental::where('vehicle_id', $vehicleId)
            ->whereNotIn('status', ['cancelled'])
            ->where('end_datetime', '>=', now())
            ->get();

        foreach ($rentals as $rental) {
            $start = $rental->start_datetime->startOfDay();
            $end = $rental->end_datetime->startOfDay();

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

        // 2. ✅ Cek jadwal travel + masa istirahat
        $schedules = Schedule::with('route')
            ->where('vehicle_id', $vehicleId)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNotNull('available_for_rental_after')
                  ->orWhereNotNull('estimated_arrival');
            })
            ->where('departure_date', '>=', now()->toDateString())
            ->get();

        foreach ($schedules as $schedule) {
            $travelDate = $schedule->departure_date;

            // Tentukan sampai kapan kendaraan tidak tersedia untuk rental
            if ($schedule->available_for_rental_after) {
                $blockUntil = Carbon::parse($schedule->available_for_rental_after);
            } elseif ($schedule->estimated_arrival) {
                $blockUntil = $schedule->estimated_arrival->copy()
                    ->addDays($schedule->rest_days_before_rental ?? 1)
                    ->startOfDay();
            } else {
                $blockUntil = $travelDate->copy()->addDay()->startOfDay();
            }

            $current = $travelDate->copy()->startOfDay();
            while ($current->lte($blockUntil)) {
                $dateStr = $current->format('Y-m-d');

                if (!isset($bookedDates[$dateStr])) {
                    $bookedDates[$dateStr] = [];
                }

                // Hindari duplikat
                $alreadyExists = collect($bookedDates[$dateStr])->contains(function ($item) {
                    return isset($item['type']) && in_array($item['type'], ['🚐 Travel', '🔧 Maintenance (Travel)']);
                });

                if (!$alreadyExists) {
                    $isTravelDay = $current->eq($travelDate);
                    $isReturnDay = $schedule->ppSchedule && 
                        $current->eq(Carbon::parse($schedule->ppSchedule->departure_date));

                    $type = '🔧 Maintenance (Travel)';
                    if ($isTravelDay) {
                        $type = '🚐 Travel: ' . $schedule->route->route_name;
                    } elseif ($isReturnDay) {
                        $type = '🚐 Travel PP: ' . ($schedule->ppSchedule->route->route_name ?? '');
                    }

                    $bookedDates[$dateStr][] = [
                        'rental_code' => null,
                        'status' => 'travel_blocked',
                        'type' => $type,
                    ];
                }

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
                    'deposit_amount' => $data['deposit_amount'] ?? 0,
                    'requirements' => $data['requirements'] ?? ['ktp' => true, 'sim' => true],
                    'photos' => $data['photos'] ?? [],
                    'terms_conditions' => $data['terms_conditions'] ?? [],
                    'refund_policy' => $data['refund_policy'] ?? [],
                    'use_system_terms' => $data['use_system_terms'] ?? true,
                    'use_system_refund' => $data['use_system_refund'] ?? true,
                    'pickup_location' => $data['pickup_location'] ?? null,
                    'pickup_maps_link' => $data['pickup_maps_link'] ?? null,
                    'use_agency_address' => $data['use_agency_address'] ?? true,
                ]
            );

            return $setting;
        });
    }

    /**
     * Dapatkan kendaraan yang tersedia untuk rental
     * (dengan filter bentrok rental + travel + masa istirahat)
     */
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

        // ✅ Filter tanggal: cek bentrok rental + travel + masa istirahat
        if (!empty($filters['date'])) {
            $date = Carbon::parse($filters['date']);

            // Cek tidak bentrok dengan rental lain
            $query->whereDoesntHave('vehicle.rentals', function ($q) use ($date) {
                $q->whereNotIn('status', ['cancelled'])
                  ->whereDate('start_datetime', '<=', $date)
                  ->whereDate('end_datetime', '>=', $date);
            });

            // ✅ Cek tidak dalam masa travel + istirahat
            $query->whereDoesntHave('vehicle.schedules', function ($q) use ($date) {
                $q->where('is_active', true)
                  ->where(function ($sq) use ($date) {
                      // Travel di tanggal tersebut
                      $sq->where('departure_date', $date->toDateString())
                         // Atau masa istirahat yang belum selesai
                         ->orWhere(function ($ssq) use ($date) {
                             $ssq->whereNotNull('available_for_rental_after')
                                 ->where('available_for_rental_after', '>', $date->toDateString())
                                 ->where('departure_date', '<=', $date->toDateString());
                         });
                  });
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
                ->lockForUpdate()
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

            // Re-check availability dengan lock yang sama
            $conflictingRental = Rental::where('vehicle_id', $data['vehicle_id'])
                ->whereNotIn('status', ['cancelled'])
                ->where(function ($q) use ($startDateTime, $endDateTime) {
                    $q->where('start_datetime', '<=', $endDateTime)
                    ->where('end_datetime', '>=', $startDateTime);
                })
                ->lockForUpdate()  // ✅ Lock conflicting rentals
                ->exists();
            
            if ($conflictingRental) {
                throw new \Exception(
                    'Maaf, kendaraan sudah dibooking untuk rentang waktu tersebut. ' .
                    'Silakan pilih tanggal atau kendaraan lain.'
                );
            }
            
            // ✅ TAMBAHKAN: Double-check jadwal travel yang bentrok
            $conflictingSchedule = Schedule::where('vehicle_id', $data['vehicle_id'])
                ->where('is_active', true)
                ->where('departure_date', '>=', $startDateTime->toDateString())
                ->where('departure_date', '<=', $endDateTime->toDateString())
                ->lockForUpdate()  // ✅ Lock conflicting schedules
                ->exists();
            
            if ($conflictingSchedule) {
                throw new \Exception(
                    'Maaf, kendaraan digunakan untuk jadwal travel pada tanggal tersebut. ' .
                    'Silakan pilih tanggal atau kendaraan lain.'
                );
            }
            
            if ($durationUnit === 'hour') {
                $duration = (int) ceil($startDateTime->diffInMinutes($endDateTime) / 60);
            } else {
                $duration = (int) ceil($startDateTime->diffInDays($endDateTime));
            }

            if ($duration < 1) {
                throw new \Exception('Durasi minimal 1 ' . ($durationUnit === 'hour' ? 'jam' : 'hari') . '.');
            }

            // ✅ Validasi ketersediaan (rental + travel + istirahat)
            if (!$this->isVehicleAvailable($data['vehicle_id'], $startDateTime, $endDateTime)) {
                // Cek apakah bentrok dengan travel
                $scheduleConflict = Schedule::with('route')
                    ->where('vehicle_id', $data['vehicle_id'])
                    ->where('is_active', true)
                    ->where(function ($q) use ($startDateTime, $endDateTime) {
                        $q->where('departure_date', '>=', $startDateTime->toDateString())
                          ->where('departure_date', '<=', $endDateTime->toDateString())
                          ->orWhere(function ($sq) use ($startDateTime) {
                              $sq->whereNotNull('available_for_rental_after')
                                 ->where('available_for_rental_after', '>=', $startDateTime->toDateString())
                                 ->where('departure_date', '<=', $startDateTime->toDateString());
                          });
                    })
                    ->first();

                if ($scheduleConflict) {
                    $blockUntil = $scheduleConflict->available_for_rental_after
                        ? Carbon::parse($scheduleConflict->available_for_rental_after)->format('d M Y')
                        : $scheduleConflict->departure_date->copy()->addDays(1)->format('d M Y');

                    throw new \Exception(
                        "Maaf, kendaraan ini tidak tersedia untuk rental pada tanggal tersebut.\n\n" .
                        "Kendaraan ini digunakan untuk travel di rute *{$scheduleConflict->route->route_name}* " .
                        "pada tanggal {$scheduleConflict->departure_date->format('d M Y')} " .
                        "dan baru tersedia untuk rental mulai {$blockUntil}.\n\n" .
                        "Silakan pilih tanggal setelah tanggal tersebut atau pilih kendaraan lain."
                    );
                }

                $conflictingRentals = $this->getConflictingRentals(
                    $data['vehicle_id'],
                    $startDateTime,
                    $endDateTime
                );

                $conflictInfo = $conflictingRentals->map(function ($r) {
                    return "• {$r->rental_code}: {$r->start_datetime->format('d M H:i')} - {$r->end_datetime->format('d M H:i')} ({$r->status_label})";
                })->join("\n");

                throw new \Exception(
                    "Maaf, kendaraan ini sudah dibooking untuk rentang waktu tersebut.\n\n" .
                    "Booking yang bentrok:\n{$conflictInfo}\n\n" .
                    "Silakan pilih tanggal lain atau kendaraan lain."
                );
            }

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

            // Generate rental code
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
            ]);

            // Proses promo
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

            // Notifikasi customer
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

    // ═══════════════════════════════════════════
    // ASSIGN DRIVER
    // ═══════════════════════════════════════════

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

        $this->notificationService->rentalDriverAssigned($rental);

        // Notifikasi customer
        if ($rental->customer->phone) {
            $this->notificationService->sendWhatsApp(
                $rental->customer->phone,
                "👨‍✈️ *Supir Telah Ditugaskan!*\n\n" .
                "Kode Rental: *{$rental->rental_code}*\n" .
                "Supir: *{$driver->name}*\n" .
                "Telepon: *{$driver->phone}*\n" .
                "Mobil: {$rental->vehicle->plate_number}\n\n" .
                "Supir akan menjemput Anda di:\n{$rental->pickup_address}"
            );
        }

        // Notifikasi driver
        if ($driver->phone) {
            $this->notificationService->sendWhatsApp(
                $driver->phone,
                "🔔 *Tugas Rental Baru!*\n\n" .
                "Kode: *{$rental->rental_code}*\n" .
                "Customer: *{$rental->customer->name}*\n" .
                "Telepon: *{$rental->customer->phone}*\n" .
                "Mobil: {$rental->vehicle->plate_number}\n" .
                "Jemput di: {$rental->pickup_address}\n" .
                "Tanggal: {$rental->start_datetime->format('d M Y H:i')}"
            );
        }

        return $rental;
    }

    // ═══════════════════════════════════════════
    // VERIFIKASI PENGAMBILAN
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
    // VERIFIKASI PENGEMBALIAN
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
    // COMPLETE RENTAL
    // ═══════════════════════════════════════════

    public function completeRental(Rental $rental): Rental
    {
        return DB::transaction(function () use ($rental) {
            if ($rental->status !== RentalStatus::RETURNED->value) {
                throw new \Exception('Rental harus dalam status Menunggu Verifikasi (returned). Status saat ini: ' . $rental->status_label);
            }

            if (!$rental->payment) {
                throw new \Exception('Rental tidak memiliki data pembayaran.');
            }

            if ($rental->payment->status !== \App\Enums\PaymentStatus::PAID->value) {
                throw new \Exception('Pembayaran rental belum dikonfirmasi. Status: ' . $rental->payment->status_label);
            }

            $rental->update(['status' => RentalStatus::COMPLETED->value]);

            $revenue = $rental->subtotal - $rental->platform_fee;

            if ($revenue > 0) {
                $this->walletService->creditRentalRevenue($rental);
            }

            if ($rental->customer->phone) {
                $this->notificationService->sendWhatsApp(
                    $rental->customer->phone,
                    "🎉 Rental *{$rental->rental_code}* telah selesai!\n\n" .
                    "Mobil: {$rental->vehicle->plate_number}\n" .
                    "Total: Rp " . number_format($rental->total_price, 0, ',', '.') . "\n\n" .
                    "Terima kasih telah menggunakan GoMad Rental."
                );
            }

            Log::info('Rental completed', [
                'rental_code' => $rental->rental_code,
                'agency_id' => $rental->agency_id,
                'revenue' => $revenue,
            ]);

            return $rental;
        });
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
            if (!in_array($rental->status, ['pending', 'paid'])) {
                throw new \Exception('Rental tidak dapat dibatalkan pada status ini.');
            }

            if ($rental->start_datetime->isPast() && $rental->status == 'paid') {
                throw new \Exception('Rental sudah dimulai, tidak dapat dibatalkan.');
            }

            $oldStatus = $rental->status;
            $cancellationFee = 0;
            $refundAmount = 0;

            if ($oldStatus === 'paid' && $rental->payment) {
                $cancellationFee = round($rental->total_price * 0.25);
                $refundAmount = $rental->total_price - $cancellationFee;
            }

            $rental->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            if ($rental->payment) {
                $paymentService = app(\App\Services\PaymentService::class);

                if ($rental->payment->payment_type === 'midtrans' && $rental->payment->status === 'paid') {
                    $paymentService->refundPaymentForRental($rental, $refundAmount);

                    Log::info('Rental refund processed', [
                        'rental_code' => $rental->rental_code,
                        'total_price' => $rental->total_price,
                        'cancellation_fee' => $cancellationFee,
                        'refund_amount' => $refundAmount,
                    ]);
                } elseif ($rental->payment->payment_type === 'cash' && $rental->payment->status === 'paid') {
                    $rental->payment->update(['status' => 'refund_pending']);

                    if ($rental->cashPayment) {
                        $rental->cashPayment->update(['status' => 'refund_pending']);
                    }
                } else {
                    $rental->payment->update(['status' => \App\Enums\PaymentStatus::EXPIRED->value]);

                    if ($rental->cashPayment) {
                        $rental->cashPayment->update(['status' => 'expired']);
                    }
                }

                if ($oldStatus === 'paid' && (float) $rental->payment->agency_revenue > 0) {
                    $agency = $rental->agency;
                    $wallet = $this->walletService->getOrCreateWallet($agency);
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
}