<?php
// File: app/Services/ScheduleService.php
// Deskripsi: Service untuk business logic jadwal, multi-stop config, dan mandatory pricing

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

    /**
     * Generate SEMUA kombinasi yang WAJIB diisi harganya
     * berdasarkan konfigurasi Pickup/Dropoff yang sudah di-set
     */
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

        // Cari semua stop yang PICKUP-nya ON
        $pickupStops = $stops->filter(function ($ss) {
            return $ss->is_pickup_available;
        });

        // Cari semua stop yang DROPOFF-nya ON
        $dropoffStops = $stops->filter(function ($ss) {
            return $ss->is_dropoff_available;
        });

        // Generate kombinasi: setiap pickup stop dengan setiap dropoff stop setelahnya
        foreach ($pickupStops as $pickupStop) {
            foreach ($dropoffStops as $dropoffStop) {
                // Dropoff harus setelah Pickup
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

    /**
     * Generate pairs yang perlu diisi saat Agency mengaktifkan Pickup/Dropoff
     * Ini dipanggil via AJAX saat agency mencentang checkbox
     */
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
            // Saat Pickup diaktifkan:
            // Cari semua stop yang DROPOFF-nya ON setelah stop ini
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
                    'reason' => "Pickup {$currentStop->routeStop->city_name} → Dropoff {$dropoffStop->routeStop->city_name}",
                ];
            }
        }

        if ($toggleType === 'dropoff' && $isEnabled) {
            // Saat Dropoff diaktifkan:
            // Cari semua stop yang PICKUP-nya ON sebelum stop ini
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
                    'reason' => "Pickup {$pickupStop->routeStop->city_name} → Dropoff {$currentStop->routeStop->city_name}",
                ];
            }
        }

        return $newPairs;
    }

    /**
     * Mendapatkan semua stop dengan status Pickup/Dropoff untuk form konfigurasi
     */
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
                'is_pickup_fixed' => $isFirst,    // Stop pertama: Pickup fixed ON
                'is_dropoff_fixed' => $isLast,    // Stop terakhir: Dropoff fixed ON
                'is_first' => $isFirst,
                'is_last' => $isLast,
            ];
        })->toArray();
    }

    /**
     * Mendapatkan daftar kota yang tersedia untuk PICKUP (untuk customer)
     */
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
                // Cari harga minimum dari stop ini
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

    /**
     * Mendapatkan daftar kota yang tersedia untuk DROPOFF setelah origin tertentu (untuk customer)
     */
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
                // Dropoff harus ON dan setelah origin
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

    public function createSchedule(array $data): Schedule
    {
        return DB::transaction(function () use ($data) {
            $route = Route::with('stops')->findOrFail($data['route_id']);
            $vehicle = Vehicle::findOrFail($data['vehicle_id']);
            
            $departureDate = Carbon::parse($data['departure_date'])->startOfDay();
            $today = Carbon::now()->startOfDay();
            $daysDiff = (int) $today->diffInDays($departureDate, false);
            
            $minDaysBefore = (int) \App\Models\PlatformSetting::getValue('schedule_min_days', 30);
            if (app()->environment('local') || app()->environment('testing')) {
                $minDaysBefore = 1;
            }

            if ($daysDiff < $minDaysBefore) {
                throw new \Exception("Jadwal harus dibuat minimal H-{$minDaysBefore} sebelum keberangkatan.");
            }

            if (!$this->checkVehicleAvailability($vehicle, $departureDate)) {
                throw new \Exception('Kendaraan sudah digunakan di jadwal lain pada tanggal tersebut.');
            }

            if (!empty($data['driver_id'])) {
                $driver = User::where('id', $data['driver_id'])
                    ->where('role', 'driver')
                    ->where('agency_id', $data['agency_id'])
                    ->first();
                if (!$driver) throw new \Exception('Driver tidak ditemukan.');
                if (!$this->checkDriverAvailability($driver, $departureDate)) {
                    throw new \Exception('Driver sudah ditugaskan di jadwal lain.');
                }
            }

            $travelClass = TravelClass::from($data['travel_class']);
            $maxOverload = $travelClass === TravelClass::ECONOMY ? (int) ($data['max_overload'] ?? 2) : 0;
            $baggageLimit = (float) ($data['baggage_limit_kg'] ?? $travelClass->maxBaggage());

            $schedule = Schedule::create([
                'agency_id' => $data['agency_id'],
                'vehicle_id' => $vehicle->id,
                'route_id' => $route->id,
                'driver_id' => $data['driver_id'] ?? null,
                'departure_date' => $departureDate->toDateString(),
                'departure_time' => $data['departure_time'],
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

            // Buat schedule stops dengan default
            $stops = $route->stops()->orderBy('stop_order')->get();
            foreach ($stops as $index => $stop) {
                $isFirst = $index === 0;
                $isLast = $index === count($stops) - 1;

                ScheduleStop::create([
                    'schedule_id' => $schedule->id,
                    'route_stop_id' => $stop->id,
                    'is_pickup_available' => $isFirst,
                    'is_dropoff_available' => $isLast,
                    'estimated_time' => null,
                ]);
            }

            // Proses stop_config jika ada (dari form JavaScript)
            if (!empty($data['stop_config'])) {
                $stopConfig = is_string($data['stop_config']) 
                    ? json_decode($data['stop_config'], true) 
                    : $data['stop_config'];
                
                if (is_array($stopConfig)) {
                    foreach ($stopConfig as $config) {
                        if (isset($config['route_stop_id'])) {
                            ScheduleStop::where('schedule_id', $schedule->id)
                                ->where('route_stop_id', $config['route_stop_id'])
                                ->update([
                                    'is_pickup_available' => $config['is_pickup_available'] ?? false,
                                    'is_dropoff_available' => $config['is_dropoff_available'] ?? false,
                                ]);
                        }
                    }
                }
            }

            // Proses pricing jika ada (dari form JavaScript)
            if (!empty($data['pricing'])) {
                $pricing = is_string($data['pricing']) 
                    ? json_decode($data['pricing'], true) 
                    : $data['pricing'];
                
                if (is_array($pricing)) {
                    foreach ($pricing as $priceItem) {
                        if (isset($priceItem['origin_stop_id'], $priceItem['destination_stop_id'], $priceItem['price'])) {
                            RoutePricing::create([
                                'schedule_id' => $schedule->id,
                                'origin_stop_id' => $priceItem['origin_stop_id'],
                                'destination_stop_id' => $priceItem['destination_stop_id'],
                                'price' => (float) $priceItem['price'],
                            ]);
                        }
                    }
                }
            }

            // Jika tidak ada pricing, generate otomatis dari harga dasar
            if (empty($data['pricing']) || (is_string($data['pricing']) && json_decode($data['pricing'], true) === [])) {
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
                                'price' => (float) $data['price_per_seat'],
                            ]);
                        }
                    }
                }
            }

            // Notifikasi driver
            if (!empty($data['driver_id'])) {
                $driver = User::find($data['driver_id']);
                if ($driver) $this->notificationService->driverAssigned($schedule, $driver);
            }

            // Di method createSchedule()
            if (!empty($data['allow_cod']) && $data['allow_cod'] == '1') {
                $route = Route::find($data['route_id']);
                
                if (!$route->cod_available) {
                    throw new \Exception('Rute ini tidak mengizinkan pembayaran COD.');
                }
                
                $minDeposit = $route->cod_min_deposit ?? 500000;
                $walletService = app(\App\Services\WalletService::class);
                $agency = Agency::find($data['agency_id']);
                
                // Cek saldo tersedia
                if (!$walletService->canActivateCod($agency, $minDeposit)) {
                    $summary = $walletService->getBalanceSummary($agency);
                    throw new \Exception('Saldo deposit tidak mencukupi. Dibutuhkan: Rp ' . number_format($minDeposit, 0, ',', '.') . ', Tersedia: Rp ' . number_format($summary['available_deposit'], 0, ',', '.'));
                }
                
                // HOLD saldo deposit untuk jadwal ini
                $walletService->holdCodDeposit($agency, $minDeposit, $schedule->id);
            }

            return $schedule->load(['route.stops', 'scheduleStops', 'routePricing', 'vehicle', 'driver']);
        });
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
                $driver = User::where('id', $data['driver_id'])
                    ->where('role', 'driver')
                    ->where('agency_id', $schedule->agency_id)
                    ->firstOrFail();
                    
                if (!$this->checkDriverAvailability($driver, $schedule->departure_date, $schedule->id)) {
                    throw new \Exception('Driver sudah ditugaskan di jadwal lain.');
                }
                $updateData['driver_id'] = $driver->id;
            }

            $allowedFields = ['departure_date', 'departure_time', 'travel_class', 'max_overload', 'price_per_seat', 'baggage_limit_kg'];
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            if (!empty($updateData)) {
                $schedule->update($updateData);
            }

            if (!empty($data['pricing'])) {
                RoutePricing::where('schedule_id', $schedule->id)->delete();
                
                $this->validateMandatoryPricing($schedule, $data['pricing']);
                
                foreach ($data['pricing'] as $priceItem) {
                    RoutePricing::create([
                        'schedule_id' => $schedule->id,
                        'origin_stop_id' => $priceItem['origin_stop_id'],
                        'destination_stop_id' => $priceItem['destination_stop_id'],
                        'price' => $priceItem['price'],
                    ]);
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
            
            if (!isset($item['price']) || $item['price'] <= 0) {
                throw new \Exception('Harga harus diisi dan lebih dari 0 untuk setiap kombinasi.');
            }
        }

        $missingPairs = [];
        foreach ($requiredPairs as $pair) {
            $key = $pair['origin_stop_id'] . '-' . $pair['destination_stop_id'];
            if (!isset($providedPairs[$key])) {
                $missingPairs[] = $pair['origin_city'] . ' → ' . $pair['destination_city'];
            }
        }

        if (!empty($missingPairs)) {
            throw new \Exception('Harga wajib diisi untuk kombinasi berikut: ' . implode(', ', $missingPairs));
        }
    }

    public function checkVehicleAvailability(Vehicle $vehicle, Carbon $date, ?int $excludeScheduleId = null): bool
    {
        $query = Schedule::where('vehicle_id', $vehicle->id)
            ->where('departure_date', $date->toDateString())
            ->where('is_active', true)
            ->whereDoesntHave('bookings', function ($q) {
                $q->where('status', 'cancelled');
            });

        if ($excludeScheduleId) {
            $query->where('id', '!=', $excludeScheduleId);
        }

        return !$query->exists();
    }

    public function checkDriverAvailability(User $driver, Carbon $date, ?int $excludeScheduleId = null): bool
    {
        if ($driver->role !== 'driver') {
            return false;
        }

        $query = Schedule::where('driver_id', $driver->id)
            ->where('departure_date', $date->toDateString())
            ->where('is_active', true);

        if ($excludeScheduleId) {
            $query->where('id', '!=', $excludeScheduleId);
        }

        return !$query->exists();
    }

    public function assignDriver(Schedule $schedule, User $driver): void
    {
        if ($driver->role !== 'driver') {
            throw new \Exception('User bukan driver.');
        }

        if ($driver->agency_id !== $schedule->agency_id) {
            throw new \Exception('Driver harus dari agency yang sama.');
        }

        if (!$this->checkDriverAvailability($driver, $schedule->departure_date, $schedule->id)) {
            throw new \Exception('Driver sudah ditugaskan di jadwal lain.');
        }

        $schedule->update(['driver_id' => $driver->id]);
        
        $this->notificationService->driverAssigned($schedule, $driver);
    }

    public function getScheduleWithPricing(Schedule $schedule): array
    {
        $schedule->load([
            'route.stops',
            'scheduleStops.routeStop',
            'routePricing.originStop',
            'routePricing.destinationStop',
            'vehicle',
            'driver',
            'agency',
        ]);

        $availableOrigins = $this->getAvailableOrigins($schedule);
        $pricingMatrix = [];

        foreach ($availableOrigins as $origin) {
            $destinations = $this->getAvailableDestinations($schedule, $origin);
            foreach ($destinations as $destination) {
                $pricing = $schedule->routePricing
                    ->where('origin_stop_id', $origin->id)
                    ->where('destination_stop_id', $destination->id)
                    ->first();
                    
                if ($pricing) {
                    $pricingMatrix[] = [
                        'origin_stop_id' => $origin->id,
                        'origin_city' => $origin->city_name,
                        'destination_stop_id' => $destination->id,
                        'destination_city' => $destination->city_name,
                        'price' => $pricing->price,
                        'pricing_id' => $pricing->id,
                    ];
                }
            }
        }

        return [
            'schedule' => $schedule,
            'pricing_matrix' => $pricingMatrix,
            'available_seats' => $schedule->available_seats,
            'max_capacity' => $schedule->max_capacity,
        ];
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
            
            if ($isLastStop) {
                continue;
            }
            
            if ($isFirstStop || $ss->is_pickup_available) {
                $origins->push($stop);
            }
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
            
            if ($stop->stop_order <= $origin->stop_order) {
                continue;
            }
            
            if ($isFirstStop) {
                continue;
            }
            
            if ($isLastStop || $ss->is_dropoff_available) {
                $destinations->push($stop);
            }
        }

        return $destinations;
    }

    /**
     * Validasi apakah agency melayani semua kota di rute ini
     */
    public function validateAgencyCoverage(Agency $agency, Route $route): bool
    {
        $allCityCodes = $route->stops->pluck('city_code')->toArray();
        
        foreach ($allCityCodes as $cityCode) {
            if (!$agency->servesCity($cityCode)) {
                $city = City::find($cityCode);
                throw new \Exception(
                    "Agency Anda tidak melayani kota {$city->name}. " .
                    "Update zona layanan di Profil Agency terlebih dahulu."
                );
            }
        }
        
        return true;
    }
}

// End of file