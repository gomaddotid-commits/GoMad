<?php

namespace App\Services;

use App\Enums\TravelClass;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Schedule;
use App\Models\Agency;
use App\Models\ScheduleStop;
use App\Models\RoutePricing;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ScheduleService
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function generateRequiredPairs(Schedule $schedule): array
    {
        $scheduleStops = $schedule->scheduleStops()
            ->with('routeStop')
            ->get()
            ->sortBy(function ($ss) {
                return $ss->routeStop->stop_order;
            });

        $stops = $scheduleStops->values();
        $pairs = [];

        $pickupStops = $stops->filter(function ($ss) {
            return $ss->is_pickup_available;
        });

        $dropoffStops = $stops->filter(function ($ss) {
            return $ss->is_dropoff_available;
        });

        foreach ($pickupStops as $pickupStop) {
            foreach ($dropoffStops as $dropoffStop) {
                if ($dropoffStop->routeStop->stop_order > $pickupStop->routeStop->stop_order) {
                    $pairs[] = [
                        'origin_stop_id' => $pickupStop->route_stop_id,
                        'origin_city' => $pickupStop->routeStop->city_name,
                        'destination_stop_id' => $dropoffStop->route_stop_id,
                        'destination_city' => $dropoffStop->routeStop->city_name,
                    ];
                }
            }
        }

        return $pairs;
    }

    public function generatePairsForStopToggle(Schedule $schedule, int $routeStopId, string $toggleType, bool $isEnabled): array
    {
        $allStops = $schedule->scheduleStops()
            ->with('routeStop')
            ->get()
            ->sortBy(function ($ss) {
                return $ss->routeStop->stop_order;
            });

        $currentStop = $allStops->firstWhere('route_stop_id', $routeStopId);
        if (!$currentStop) return [];

        $newPairs = [];

        if ($toggleType === 'pickup' && $isEnabled) {
            $dropoffStops = $allStops->filter(function ($ss) use ($currentStop) {
                return $ss->is_dropoff_available && 
                       $ss->routeStop->stop_order > $currentStop->routeStop->stop_order;
            });

            foreach ($dropoffStops as $dropoffStop) {
                $newPairs[] = [
                    'origin_stop_id' => $routeStopId,
                    'origin_city' => $currentStop->routeStop->city_name,
                    'destination_stop_id' => $dropoffStop->route_stop_id,
                    'destination_city' => $dropoffStop->routeStop->city_name,
                ];
            }
        }

        if ($toggleType === 'dropoff' && $isEnabled) {
            $pickupStops = $allStops->filter(function ($ss) use ($currentStop) {
                return $ss->is_pickup_available && 
                       $ss->routeStop->stop_order < $currentStop->routeStop->stop_order;
            });

            foreach ($pickupStops as $pickupStop) {
                $newPairs[] = [
                    'origin_stop_id' => $pickupStop->route_stop_id,
                    'origin_city' => $pickupStop->routeStop->city_name,
                    'destination_stop_id' => $routeStopId,
                    'destination_city' => $currentStop->routeStop->city_name,
                ];
            }
        }

        return $newPairs;
    }

    public function getStopConfiguration(Schedule $schedule): array
    {
        $scheduleStops = $schedule->scheduleStops()
            ->with('routeStop')
            ->get()
            ->sortBy(function ($ss) {
                return $ss->routeStop->stop_order;
            });

        $stops = $scheduleStops->values();
        $totalStops = count($stops);

        return $stops->map(function ($ss, $index) use ($totalStops) {
            $isFirst = $index === 0;
            $isLast = $index === $totalStops - 1;

            return [
                'schedule_stop_id' => $ss->id,
                'route_stop_id' => $ss->route_stop_id,
                'city_name' => $ss->routeStop->city_name,
                'stop_order' => $ss->routeStop->stop_order,
                'is_pickup_available' => (bool) $ss->is_pickup_available,
                'is_dropoff_available' => (bool) $ss->is_dropoff_available,
                'is_pickup_fixed' => $isFirst,
                'is_dropoff_fixed' => $isLast,
                'is_first' => $isFirst,
                'is_last' => $isLast,
            ];
        })->toArray();
    }

    public function getAvailablePickupStops(Schedule $schedule): array
    {
        $scheduleStops = $schedule->scheduleStops()
            ->with('routeStop')
            ->get()
            ->sortBy(function ($ss) {
                return $ss->routeStop->stop_order;
            });

        return $scheduleStops
            ->filter(function ($ss) {
                return $ss->is_pickup_available;
            })
            ->map(function ($ss) use ($schedule) {
                $minPrice = RoutePricing::where('schedule_id', $schedule->id)
                    ->where('origin_stop_id', $ss->route_stop_id)
                    ->min('price');

                return [
                    'route_stop_id' => $ss->route_stop_id,
                    'city_name' => $ss->routeStop->city_name,
                    'stop_order' => $ss->routeStop->stop_order,
                    'min_price' => $minPrice ? (float) $minPrice : null,
                    'min_price_formatted' => $minPrice ? 'Rp ' . number_format($minPrice, 0, ',', '.') : 'Belum ada harga',
                ];
            })
            ->values()
            ->toArray();
    }

    public function getAvailableDropoffStops(Schedule $schedule, int $originStopId): array
    {
        $originStop = RouteStop::findOrFail($originStopId);

        $scheduleStops = $schedule->scheduleStops()
            ->with('routeStop')
            ->get()
            ->sortBy(function ($ss) {
                return $ss->routeStop->stop_order;
            });

        return $scheduleStops
            ->filter(function ($ss) use ($originStop) {
                return $ss->is_dropoff_available && 
                       $ss->routeStop->stop_order > $originStop->stop_order;
            })
            ->map(function ($ss) use ($schedule, $originStopId) {
                $pricing = RoutePricing::where('schedule_id', $schedule->id)
                    ->where('origin_stop_id', $originStopId)
                    ->where('destination_stop_id', $ss->route_stop_id)
                    ->first();

                return [
                    'route_stop_id' => $ss->route_stop_id,
                    'city_name' => $ss->routeStop->city_name,
                    'stop_order' => $ss->routeStop->stop_order,
                    'price' => $pricing ? (float) $pricing->price : null,
                    'price_formatted' => $pricing ? 'Rp ' . number_format($pricing->price, 0, ',', '.') : 'Belum ada harga',
                    'has_price' => !is_null($pricing),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * CREATE SCHEDULE — tanpa fallback PP
     */
    public function createSchedule(array $data): Schedule
    {
        return DB::transaction(function () use ($data) {
            $route = Route::with('stops')->findOrFail($data['route_id']);
            $vehicle = Vehicle::with('rentalSetting')->findOrFail($data['vehicle_id']);
            $agency = Agency::findOrFail($data['agency_id']);
        
            $this->validateAgencyCoverage($agency, $route);
            
            // ═══════════════════════════════════
            // VALIDASI TANGGAL
            // ═══════════════════════════════════
            $departureDate = Carbon::parse($data['departure_date'])->startOfDay();
            $today = Carbon::now()->startOfDay();
            $daysDiff = (int) $today->diffInDays($departureDate, false);

            $minDaysBefore = (int) \App\Models\PlatformSetting::getValue('schedule_min_days', 30);
            if (app()->environment('local', 'testing')) {
                $minDaysBefore = 1;
            }

            if ($daysDiff < $minDaysBefore) {
                throw new \Exception("Jadwal harus dibuat minimal H-{$minDaysBefore} sebelum keberangkatan.");
            }

            // ═══════════════════════════════════
            // VALIDASI KETERSEDIAAN KENDARAAN
            // ═══════════════════════════════════
            if (!$this->checkVehicleAvailability($vehicle, $departureDate)) {
                $conflictDetail = $this->getVehicleConflictDetail($vehicle, $departureDate);
                throw new \Exception($conflictDetail);
            }

            // ═══════════════════════════════════
            // HITUNG ESTIMASI TIBA
            // ═══════════════════════════════════
            $estimatedArrival = null;
            if ($route->estimated_duration) {
                $departureDateTime = Carbon::parse($departureDate->format('Y-m-d') . ' ' . $data['departure_time']);
                $estimatedArrival = $departureDateTime->copy()->addMinutes($route->estimated_duration);
            } elseif (isset($data['estimated_arrival'])) {
                $estimatedArrival = Carbon::parse($data['estimated_arrival']);
            }

            // ═══════════════════════════════════
            // VALIDASI DRIVER
            // ═══════════════════════════════════
            if (!empty($data['driver_id'])) {
                $driver = User::where('id', $data['driver_id'])
                    ->where('role', 'driver')
                    ->where('agency_id', $data['agency_id'])
                    ->first();
                if (!$driver) throw new \Exception('Driver tidak ditemukan.');
                if (!$this->checkDriverAvailability($driver, $departureDate)) {
                    throw new \Exception('Driver sudah ditugaskan di jadwal lain pada tanggal tersebut.');
                }
            }

            // ═══════════════════════════════════
            // BUAT SCHEDULE PERGI
            // ═══════════════════════════════════
            $travelClass = TravelClass::from($data['travel_class']);
            $maxOverload = $travelClass === TravelClass::ECONOMY ? (int) ($data['max_overload'] ?? 2) : 0;
            $baggageLimit = (float) ($data['baggage_limit_kg'] ?? $travelClass->maxBaggage());

            $isRentalVehicle = $vehicle->rentalSetting && $vehicle->rentalSetting->is_available_for_rental;
            $restDaysBeforeRental = (int) ($data['rest_days_before_rental'] ?? 1);

            $scheduleGo = Schedule::create([
                'agency_id' => $data['agency_id'],
                'vehicle_id' => $vehicle->id,
                'route_id' => $route->id,
                'driver_id' => $data['driver_id'] ?? null,
                'departure_date' => $departureDate->toDateString(),
                'departure_time' => $data['departure_time'],
                'estimated_arrival' => $estimatedArrival,
                'pp_rest_hours' => $data['pp_rest_hours'] ?? 2,
                'rest_days_before_rental' => $restDaysBeforeRental,
                'travel_class' => $travelClass->value,
                'max_overload' => $maxOverload,
                'price_per_seat' => (float) $data['price_per_seat'],
                'baggage_limit_kg' => $baggageLimit,
                'is_active' => true,
                'allow_passenger_transfer' => true,
                'accept_external_transfer' => true,
                'transfer_fee_per_passenger' => 20000,
                'allow_cod' => !empty($data['allow_cod']) && $data['allow_cod'] == '1',
                'cod_min_balance' => $route->cod_min_deposit ?? 500000,
            ]);

            // Buat schedule stops dasar
            $stops = $route->stops()->orderBy('stop_order')->get();
            foreach ($stops as $index => $stop) {
                $isFirst = $index === 0;
                $isLast = $index === count($stops) - 1;
                ScheduleStop::create([
                    'schedule_id' => $scheduleGo->id,
                    'route_stop_id' => $stop->id,
                    'is_pickup_available' => $isFirst,
                    'is_dropoff_available' => $isLast,
                    'estimated_time' => null,
                ]);
            }

            // Apply stop_config dari form
            if (!empty($data['stop_config'])) {
                $stopConfig = is_string($data['stop_config']) ? json_decode($data['stop_config'], true) : $data['stop_config'];
                if (is_array($stopConfig)) {
                    foreach ($stopConfig as $config) {
                        if (isset($config['route_stop_id'])) {
                            ScheduleStop::where('schedule_id', $scheduleGo->id)
                                ->where('route_stop_id', $config['route_stop_id'])
                                ->update([
                                    'is_pickup_available' => $config['is_pickup_available'] ?? false,
                                    'is_dropoff_available' => $config['is_dropoff_available'] ?? false,
                                ]);
                        }
                    }
                }
            }

            // Apply pricing pergi
            if (!empty($data['pricing'])) {
                $pricing = is_string($data['pricing']) ? json_decode($data['pricing'], true) : $data['pricing'];
                if (is_array($pricing)) {
                    foreach ($pricing as $priceItem) {
                        if (isset($priceItem['origin_stop_id'], $priceItem['destination_stop_id'], $priceItem['price'])) {
                            RoutePricing::create([
                                'schedule_id' => $scheduleGo->id,
                                'origin_stop_id' => $priceItem['origin_stop_id'],
                                'destination_stop_id' => $priceItem['destination_stop_id'],
                                'price' => (float) $priceItem['price'],
                            ]);
                        }
                    }
                }
            } else {
                $this->autoGeneratePricing($scheduleGo, (float) $data['price_per_seat']);
            }

            // ═══════════════════════════════════
            // BUAT SCHEDULE PP (JIKA DIAKTIFKAN)
            // ═══════════════════════════════════
            $schedulePP = null;
            $isPP = !empty($data['is_pp']) && $data['is_pp'] == '1';

            if ($isPP) {
                // ✅ VALIDASI KETAT: pp_pricing & pp_stop_config WAJIB diisi
                if (empty($data['pp_pricing'])) {
                    throw new \Exception('Harga untuk jadwal PP wajib diisi. Silakan kembali ke Step 2B dan isi semua kombinasi harga PP.');
                }
                if (empty($data['pp_stop_config'])) {
                    throw new \Exception('Konfigurasi stop untuk jadwal PP wajib diisi. Silakan kembali ke Step 2B.');
                }
                if (empty($data['pp_date'])) {
                    throw new \Exception('Tanggal PP harus diisi.');
                }

                $ppDateTime = Carbon::parse($data['pp_date'] . ' ' . ($data['pp_time'] ?? $data['departure_time']));

                // Validasi: PP minimal setelah estimasi tiba + istirahat
                if ($estimatedArrival) {
                    $minPPDateTime = $estimatedArrival->copy()->addHours($scheduleGo->pp_rest_hours);
                    if ($ppDateTime->lt($minPPDateTime)) {
                        throw new \Exception(
                            "Tanggal PP minimal adalah " . $minPPDateTime->format('d M Y H:i') .
                            " (estimasi tiba + istirahat {$scheduleGo->pp_rest_hours} jam)."
                        );
                    }
                }

                // Cari atau buat rute kebalikan
                $returnRoute = Route::where('origin_city_code', $route->destination_city_code)
                    ->where('destination_city_code', $route->origin_city_code)
                    ->where('is_active', true)
                    ->first();

                if (!$returnRoute) {
                    $returnRoute = $this->createReturnRoute($route);
                }

                // Validasi ketersediaan kendaraan untuk PP
                $ppDate = $ppDateTime->copy()->startOfDay();
                if (!$this->checkVehicleAvailability($vehicle, $ppDate, $scheduleGo->id)) {
                    $conflictDetail = $this->getVehicleConflictDetail($vehicle, $ppDate);
                    throw new \Exception("Kendaraan tidak tersedia untuk jadwal PP. " . $conflictDetail);
                }

                // Validasi driver untuk PP
                if (!empty($data['driver_id'])) {
                    if (!$this->checkDriverAvailability($driver, $ppDate, $scheduleGo->id)) {
                        throw new \Exception('Driver sudah ditugaskan di jadwal lain pada tanggal PP.');
                    }
                }

                // Hitung estimasi tiba PP
                $estimatedArrivalPP = null;
                if ($returnRoute->estimated_duration) {
                    $estimatedArrivalPP = $ppDateTime->copy()->addMinutes($returnRoute->estimated_duration);
                }

                // Buat schedule PP
                $schedulePP = Schedule::create([
                    'agency_id' => $data['agency_id'],
                    'vehicle_id' => $vehicle->id,
                    'route_id' => $returnRoute->id,
                    'driver_id' => $data['driver_id'] ?? null,
                    'departure_date' => $ppDate->toDateString(),
                    'departure_time' => $ppDateTime->format('H:i'),
                    'estimated_arrival' => $estimatedArrivalPP,
                    'parent_schedule_id' => $scheduleGo->id,
                    'travel_class' => $travelClass->value,
                    'max_overload' => $maxOverload,
                    'price_per_seat' => (float) ($data['pp_price'] ?? $data['price_per_seat']),
                    'baggage_limit_kg' => $baggageLimit,
                    'is_active' => true,
                    'allow_passenger_transfer' => true,
                    'accept_external_transfer' => true,
                    'transfer_fee_per_passenger' => 20000,
                    'allow_cod' => !empty($data['allow_cod']) && $data['allow_cod'] == '1',
                    'cod_min_balance' => $returnRoute->cod_min_deposit ?? 500000,
                ]);

                $scheduleGo->update(['pp_schedule_id' => $schedulePP->id]);

                // Buat schedule stops dasar untuk PP
                $returnStops = $returnRoute->stops()->orderBy('stop_order')->get();
                foreach ($returnStops as $index => $stop) {
                    $isFirst = $index === 0;
                    $isLast = $index === count($returnStops) - 1;
                    ScheduleStop::create([
                        'schedule_id' => $schedulePP->id,
                        'route_stop_id' => $stop->id,
                        'is_pickup_available' => $isFirst,
                        'is_dropoff_available' => $isLast,
                        'estimated_time' => null,
                    ]);
                }

                // Apply pp_stop_config dari form (WAJIB)
                $ppStopConfig = is_string($data['pp_stop_config']) ? json_decode($data['pp_stop_config'], true) : $data['pp_stop_config'];
                if (is_array($ppStopConfig)) {
                    foreach ($ppStopConfig as $config) {
                        if (isset($config['route_stop_id'])) {
                            ScheduleStop::where('schedule_id', $schedulePP->id)
                                ->where('route_stop_id', $config['route_stop_id'])
                                ->update([
                                    'is_pickup_available' => $config['is_pickup_available'] ?? false,
                                    'is_dropoff_available' => $config['is_dropoff_available'] ?? false,
                                ]);
                        }
                    }
                }

                // Apply pp_pricing dari form (WAJIB)
                $ppPricing = is_string($data['pp_pricing']) ? json_decode($data['pp_pricing'], true) : $data['pp_pricing'];
                if (is_array($ppPricing)) {
                    foreach ($ppPricing as $priceItem) {
                        if (isset($priceItem['origin_stop_id'], $priceItem['destination_stop_id'], $priceItem['price'])) {
                            RoutePricing::create([
                                'schedule_id' => $schedulePP->id,
                                'origin_stop_id' => $priceItem['origin_stop_id'],
                                'destination_stop_id' => $priceItem['destination_stop_id'],
                                'price' => (float) $priceItem['price'],
                            ]);
                        }
                    }
                }

                // SET KETERSEDIAAN RENTAL
                if ($isRentalVehicle) {
                    $lastArrival = $estimatedArrivalPP ?? $ppDateTime;
                    $availableForRentalAfter = $lastArrival->copy()->addDays($restDaysBeforeRental)->startOfDay();
                    $schedulePP->update(['available_for_rental_after' => $availableForRentalAfter->toDateString()]);
                }
            } else {
                // Tidak PP
                if ($isRentalVehicle) {
                    $lastArrival = $estimatedArrival ?? Carbon::parse($departureDate->format('Y-m-d') . ' ' . $data['departure_time']);
                    $availableForRentalAfter = $lastArrival->copy()->addDays($restDaysBeforeRental)->startOfDay();
                    $scheduleGo->update(['available_for_rental_after' => $availableForRentalAfter->toDateString()]);
                }
            }

            // ═══════════════════════════════════
            // HOLD COD DEPOSIT
            // ═══════════════════════════════════
            if (!empty($data['allow_cod']) && $data['allow_cod'] == '1') {
                if (!$route->cod_available) {
                    throw new \Exception('Rute ini tidak mengizinkan pembayaran COD.');
                }
                $minDeposit = $route->cod_min_deposit ?? 500000;
                $walletService = app(\App\Services\WalletService::class);
                $agency = Agency::find($data['agency_id']);
                if (!$walletService->canActivateCod($agency, $minDeposit)) {
                    $summary = $walletService->getBalanceSummary($agency);
                    throw new \Exception('Saldo deposit tidak mencukupi. Dibutuhkan: Rp ' . number_format($minDeposit, 0, ',', '.') . ', Tersedia: Rp ' . number_format($summary['available_deposit'], 0, ',', '.'));
                }
                $walletService->holdCodDeposit($agency, $minDeposit, $scheduleGo->id);
                if ($schedulePP) {
                    $walletService->holdCodDeposit($agency, $minDeposit, $schedulePP->id);
                }
            }

            // Notifikasi driver
            if (!empty($data['driver_id'])) {
                $driver = User::find($data['driver_id']);
                if ($driver) {
                    $this->notificationService->driverAssigned($scheduleGo, $driver);
                    if ($schedulePP) {
                        $this->notificationService->driverAssigned($schedulePP, $driver);
                    }
                }
            }

            return $scheduleGo->load(['route.stops', 'scheduleStops', 'routePricing', 'vehicle', 'driver', 'ppSchedule']);
        });
    }

    private function autoGeneratePricing(Schedule $schedule, float $pricePerSeat): void
    {
        $scheduleStops = $schedule->scheduleStops()->with('routeStop')->get();
        $pickupStops = $scheduleStops->where('is_pickup_available', true);
        $dropoffStops = $scheduleStops->where('is_dropoff_available', true);
        foreach ($pickupStops as $pickup) {
            foreach ($dropoffStops as $dropoff) {
                if ($dropoff->routeStop->stop_order > $pickup->routeStop->stop_order) {
                    RoutePricing::create([
                        'schedule_id' => $schedule->id,
                        'origin_stop_id' => $pickup->route_stop_id,
                        'destination_stop_id' => $dropoff->route_stop_id,
                        'price' => $pricePerSeat,
                    ]);
                }
            }
        }
    }

    private function createReturnRoute(Route $goRoute): Route
{
    $origin = $goRoute->destinationCity;
    $destination = $goRoute->originCity;

    $distance = app(RouteService::class)->calculateDistance(
        $origin->latitude ?? 0, $origin->longitude ?? 0,
        $destination->latitude ?? 0, $destination->longitude ?? 0
    );

    $returnRoute = Route::create([
        'route_name' => "{$origin->name} - {$destination->name}",
        'origin_city_code' => $origin->code,
        'destination_city_code' => $destination->code,
        'distance_km' => $distance,
        'estimated_duration' => $goRoute->estimated_duration,
        'max_price' => $goRoute->max_price,
        'cod_available' => $goRoute->cod_available,
        'cod_min_deposit' => $goRoute->cod_min_deposit,
        'payment_methods' => $goRoute->payment_methods,
        'is_active' => true,
        'is_system_generated' => true,
    ]);

    // ═══════════════════════════════════════
    // BUAT STOP KEBALIKAN — PASTIKAN TERBALIK!
    // ═══════════════════════════════════════
    $originalStops = $goRoute->stops()->orderBy('stop_order')->get();
    $reversedStops = $originalStops->reverse()->values();
    $totalStops = $reversedStops->count();

    foreach ($reversedStops as $index => $stop) {
        // Hitung distance_from_origin untuk stop yang sudah dibalik
        $newDistance = $distance - ($stop->distance_from_origin ?? 0);
        
        RouteStop::create([
            'route_id' => $returnRoute->id,
            'city_code' => $stop->city_code,
            'stop_order' => $index + 1,
            'latitude' => $stop->latitude,
            'longitude' => $stop->longitude,
            'distance_from_origin' => max(0, $newDistance),
        ]);
    }

    return $returnRoute->load('stops');
}

    private function getVehicleConflictDetail(Vehicle $vehicle, Carbon $date): string
    {
        $existingSchedule = Schedule::where('vehicle_id', $vehicle->id)
            ->where('departure_date', $date->toDateString())
            ->where('is_active', true)
            ->with('route')
            ->first();
        if ($existingSchedule) {
            return "Kendaraan {$vehicle->plate_number} sudah dijadwalkan untuk rute {$existingSchedule->route->route_name} pada tanggal {$date->format('d M Y')}.";
        }
        $rentalConflict = \App\Models\Rental::where('vehicle_id', $vehicle->id)
            ->whereNotIn('status', ['cancelled'])
            ->whereDate('start_datetime', '<=', $date)
            ->whereDate('end_datetime', '>=', $date)
            ->with('customer')
            ->first();
        if ($rentalConflict) {
            return "Kendaraan {$vehicle->plate_number} sedang dibooking rental:\n• Kode: {$rentalConflict->rental_code}\n• Customer: {$rentalConflict->customer->name}\n• Periode: {$rentalConflict->start_datetime->format('d M')} - {$rentalConflict->end_datetime->format('d M Y')}\n\nSilakan pilih kendaraan lain atau tanggal lain.";
        }
        return "Kendaraan {$vehicle->plate_number} tidak tersedia pada tanggal {$date->format('d M Y')}.";
    }

    public function updateSchedule(Schedule $schedule, array $data): Schedule
    {
        return DB::transaction(function () use ($schedule, $data) {
            if ($schedule->bookings()->whereNotIn('status', ['cancelled'])->exists()) {
                throw new \Exception('Jadwal sudah memiliki booking, tidak dapat diubah.');
            }
            $updateData = [];
            if (isset($data['vehicle_id'])) {
                $vehicle = Vehicle::findOrFail($data['vehicle_id']);
                if (!$this->checkVehicleAvailability($vehicle, $schedule->departure_date, $schedule->id)) {
                    throw new \Exception('Kendaraan sudah digunakan di jadwal lain.');
                }
                $updateData['vehicle_id'] = $vehicle->id;
            }
            if (isset($data['driver_id'])) {
                $driver = User::where('id', $data['driver_id'])->where('role', 'driver')->where('agency_id', $schedule->agency_id)->firstOrFail();
                if (!$this->checkDriverAvailability($driver, $schedule->departure_date, $schedule->id)) {
                    throw new \Exception('Driver sudah ditugaskan di jadwal lain.');
                }
                $updateData['driver_id'] = $driver->id;
            }
            $allowedFields = ['departure_date', 'departure_time', 'travel_class', 'max_overload', 'price_per_seat', 'baggage_limit_kg'];
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) $updateData[$field] = $data[$field];
            }
            if (!empty($updateData)) $schedule->update($updateData);
            if (!empty($data['pricing'])) {
                RoutePricing::where('schedule_id', $schedule->id)->delete();
                $this->validateMandatoryPricing($schedule, $data['pricing']);
                foreach ($data['pricing'] as $priceItem) {
                    RoutePricing::create(['schedule_id' => $schedule->id, 'origin_stop_id' => $priceItem['origin_stop_id'], 'destination_stop_id' => $priceItem['destination_stop_id'], 'price' => $priceItem['price']]);
                }
            }
            return $schedule->fresh()->load(['route.stops', 'scheduleStops', 'routePricing', 'vehicle', 'driver']);
        });
    }

    public function validateMandatoryPricing(Schedule $schedule, array $pricingData): void
    {
        $requiredPairs = $this->generateRequiredPairs($schedule);
        $providedPairs = [];
        foreach ($pricingData as $item) {
            $key = $item['origin_stop_id'] . '-' . $item['destination_stop_id'];
            $providedPairs[$key] = true;
            if (!isset($item['price']) || $item['price'] <= 0) throw new \Exception('Harga harus diisi dan lebih dari 0.');
        }
        $missingPairs = [];
        foreach ($requiredPairs as $pair) {
            $key = $pair['origin_stop_id'] . '-' . $pair['destination_stop_id'];
            if (!isset($providedPairs[$key])) $missingPairs[] = $pair['origin_city'] . ' → ' . $pair['destination_city'];
        }
        if (!empty($missingPairs)) throw new \Exception('Harga wajib diisi: ' . implode(', ', $missingPairs));
    }

    public function checkVehicleAvailability(Vehicle $vehicle, Carbon $date, ?int $excludeScheduleId = null): bool
    {
        $query = Schedule::where('vehicle_id', $vehicle->id)->where('departure_date', $date->toDateString())->where('is_active', true);
        if ($excludeScheduleId) $query->where('id', '!=', $excludeScheduleId);
        if ($query->exists()) return false;
        if (\App\Models\Rental::where('vehicle_id', $vehicle->id)->whereNotIn('status', ['cancelled'])->whereDate('start_datetime', '<=', $date)->whereDate('end_datetime', '>=', $date)->exists()) return false;
        return true;
    }

    public function checkDriverAvailability(User $driver, Carbon $date, ?int $excludeScheduleId = null): bool
    {
        if ($driver->role !== 'driver') return false;
        $query = Schedule::where('driver_id', $driver->id)->where('departure_date', $date->toDateString())->where('is_active', true);
        if ($excludeScheduleId) $query->where('id', '!=', $excludeScheduleId);
        return !$query->exists();
    }

    public function assignDriver(Schedule $schedule, User $driver): void
    {
        if ($driver->role !== 'driver') throw new \Exception('User bukan driver.');
        if ($driver->agency_id !== $schedule->agency_id) throw new \Exception('Driver harus dari agency yang sama.');
        if (!$this->checkDriverAvailability($driver, $schedule->departure_date, $schedule->id)) throw new \Exception('Driver sudah ditugaskan di jadwal lain.');
        $schedule->update(['driver_id' => $driver->id]);
        $this->notificationService->driverAssigned($schedule, $driver);
    }

    public function getScheduleWithPricing(Schedule $schedule): array
    {
        $schedule->load(['route.stops', 'scheduleStops.routeStop', 'routePricing.originStop', 'routePricing.destinationStop', 'vehicle', 'driver', 'agency']);
        $availableOrigins = $this->getAvailableOrigins($schedule);
        $pricingMatrix = [];
        foreach ($availableOrigins as $origin) {
            $destinations = $this->getAvailableDestinations($schedule, $origin);
            foreach ($destinations as $destination) {
                $pricing = $schedule->routePricing->where('origin_stop_id', $origin->id)->where('destination_stop_id', $destination->id)->first();
                if ($pricing) {
                    $pricingMatrix[] = ['origin_stop_id' => $origin->id, 'origin_city' => $origin->city_name, 'destination_stop_id' => $destination->id, 'destination_city' => $destination->city_name, 'price' => $pricing->price, 'pricing_id' => $pricing->id];
                }
            }
        }
        return ['schedule' => $schedule, 'pricing_matrix' => $pricingMatrix, 'available_seats' => $schedule->available_seats, 'max_capacity' => $schedule->max_capacity];
    }

    public function getAvailableOrigins(Schedule $schedule): Collection
    {
        $scheduleStops = $schedule->scheduleStops()->with('routeStop')->get();
        $allStops = $schedule->route->stops;
        $origins = collect();
        foreach ($scheduleStops as $ss) {
            $stop = $ss->routeStop;
            $isFirstStop = $stop->stop_order === $allStops->min('stop_order');
            $isLastStop = $stop->stop_order === $allStops->max('stop_order');
            if ($isLastStop) continue;
            if ($isFirstStop || $ss->is_pickup_available) $origins->push($stop);
        }
        return $origins;
    }

    public function getAvailableDestinations(Schedule $schedule, RouteStop $origin): Collection
    {
        $scheduleStops = $schedule->scheduleStops()->with('routeStop')->get();
        $allStops = $schedule->route->stops;
        $destinations = collect();
        foreach ($scheduleStops as $ss) {
            $stop = $ss->routeStop;
            $isFirstStop = $stop->stop_order === $allStops->min('stop_order');
            $isLastStop = $stop->stop_order === $allStops->max('stop_order');
            if ($stop->stop_order <= $origin->stop_order) continue;
            if ($isFirstStop) continue;
            if ($isLastStop || $ss->is_dropoff_available) $destinations->push($stop);
        }
        return $destinations;
    }

    public function validateAgencyCoverage(Agency $agency, Route $route): bool
    {
        foreach ($route->stops->pluck('city_code') as $cityCode) {
            if (!$agency->servesCity($cityCode)) {
                $city = \App\Models\City::find($cityCode);
                throw new \Exception("Agency Anda tidak melayani kota {$city->name}. Update zona layanan di Profil Agency.");
            }
        }
        return true;
    }
}