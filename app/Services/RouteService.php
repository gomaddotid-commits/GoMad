<?php
// File: app/Services/RouteService.php
// Deskripsi: Service untuk pencarian rute dan jadwal

namespace App\Services;

use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RouteService
{
    public function searchSchedules(string $originCity, string $destinationCity, Carbon $date, ?array $filters = []): Collection
    {
        $query = Schedule::with([
            'route.stops',
            'agency',
            'vehicle',
            'scheduleStops.routeStop',
            'routePricing',
        ])
        ->where('is_active', true)
        ->where('departure_date', $date->toDateString())
        ->whereHas('route', function ($q) use ($originCity, $destinationCity) {
            $q->where(function ($subQ) use ($originCity, $destinationCity) {
                $subQ->whereHas('stops', function ($stopQ) use ($originCity) {
                    $stopQ->where('city_name', 'like', "%{$originCity}%");
                })->whereHas('stops', function ($stopQ) use ($destinationCity) {
                    $stopQ->where('city_name', 'like', "%{$destinationCity}%");
                });
            });
        });

        if (!empty($filters['travel_class'])) {
            $query->where('travel_class', $filters['travel_class']);
        }

        if (!empty($filters['agency_id'])) {
            $query->where('agency_id', $filters['agency_id']);
        }

        if (!empty($filters['min_seats'])) {
            $query->whereHas('bookings', function ($q) {
                $q->whereNotIn('status', ['cancelled']);
            }, '<', \DB::raw('(SELECT capacity FROM vehicles WHERE vehicles.id = schedules.vehicle_id) + schedules.max_overload - ' . $filters['min_seats']));
        }

        $schedules = $query->orderBy('departure_time')->get();

        // Filter schedules that have valid pricing for the origin-destination combination
        $validSchedules = $schedules->filter(function ($schedule) use ($originCity, $destinationCity) {
            $originStop = $schedule->route->stops->first(function ($stop) use ($originCity) {
                return stripos($stop->city_name, $originCity) !== false;
            });
            
            $destStop = $schedule->route->stops->first(function ($stop) use ($destinationCity) {
                return stripos($stop->city_name, $destinationCity) !== false;
            });
            
            if (!$originStop || !$destStop) {
                return false;
            }
            
            if ($originStop->stop_order >= $destStop->stop_order) {
                return false;
            }
            
            // Check if pricing exists
            $hasPricing = $schedule->routePricing
                ->where('origin_stop_id', $originStop->id)
                ->where('destination_stop_id', $destStop->id)
                ->isNotEmpty();
            
            return $hasPricing;
        });

        return $validSchedules->values();
    }

    public function getAvailableOrigins(Schedule $schedule): Collection
    {
        return app(ScheduleService::class)->getAvailableOrigins($schedule);
    }

    public function getAvailableDestinations(Schedule $schedule, RouteStop $origin): Collection
    {
        return app(ScheduleService::class)->getAvailableDestinations($schedule, $origin);
    }

    public function getAllCities(): Collection
    {
        return RouteStop::select('city_name')
            ->distinct()
            ->orderBy('city_name')
            ->get()
            ->pluck('city_name');
    }

    public function getOriginCities(): Collection
    {
        return RouteStop::select('city_name')
            ->distinct()
            ->where('stop_order', '>', 0)
            ->orderBy('city_name')
            ->get()
            ->pluck('city_name');
    }

    public function getDestinationCities(): Collection
    {
        return RouteStop::select('city_name')
            ->distinct()
            ->orderBy('city_name')
            ->get()
            ->pluck('city_name');
    }

    public function getAllRoutes(): Collection
    {
        return Route::with('stops')
            ->where('is_active', true)
            ->orderBy('route_name')
            ->get();
    }

    public function getPopularRoutes(int $limit = 5): Collection
    {
        return Route::withCount(['schedules' => function ($query) {
            $query->where('departure_date', '>=', now()->toDateString())
                ->where('is_active', true);
        }])
        ->where('is_active', true)
        ->orderByDesc('schedules_count')
        ->limit($limit)
        ->get();
    }

    public function createRoute(array $data): Route
    {
        $route = Route::create([
            'route_name' => $data['route_name'],
            'origin_city' => $data['origin_city'],
            'destination_city' => $data['destination_city'],
            'distance_km' => $data['distance_km'] ?? null,
            'estimated_duration' => $data['estimated_duration'] ?? null,
            'max_price' => $data['max_price'] ?? null,
            'cod_min_deposit' => $data['cod_min_deposit'] ?? null,
            'cod_available' => $data['cod_available'] ?? null,
            'payment_methods' => $data['payment_methods'] ?? null,  // 👈 TAMBAHKAN
            'description' => $data['description'] ?? null,
            'photo' => $data['photo'] ?? null,
            'is_active' => true,
        ]);

        if (!empty($data['stops']) && is_array($data['stops'])) {
            foreach ($data['stops'] as $index => $stopData) {
                RouteStop::create([
                    'route_id' => $route->id,
                    'city_name' => $stopData['city_name'],
                    'stop_order' => $stopData['stop_order'] ?? ($index + 1),
                    'latitude' => $stopData['latitude'] ?? null,
                    'longitude' => $stopData['longitude'] ?? null,
                    'distance_from_origin' => $stopData['distance_from_origin'] ?? null,
                ]);
            }
        }

        return $route->load('stops');
    }

    public function updateRoute(Route $route, array $data): Route
    {
        $route->update([
            'route_name' => $data['route_name'] ?? $route->route_name,
            'origin_city' => $data['origin_city'] ?? $route->origin_city,
            'destination_city' => $data['destination_city'] ?? $route->destination_city,
            'distance_km' => $data['distance_km'] ?? $route->distance_km,
            'estimated_duration' => $data['estimated_duration'] ?? $route->estimated_duration,
            'max_price' => $data['max_price'] ?? $route->max_price,
            'cod_min_deposit' => $data['cod_min_deposit'] ?? $route->cod_min_deposit,
            'cod_available' => $data['cod_available'] ?? $route->cod_available,
            'payment_methods' => $data['payment_methods'] ?? $route->payment_methods,  // 👈 TAMBAHKAN
            'description' => $data['description'] ?? $route->description,
            'photo' => $data['photo'] ?? $route->photo,           // 👈 Tambahkan
            'is_active' => $data['is_active'] ?? $route->is_active,
        ]);

        return $route->fresh()->load('stops');
    }

    public function addStop(Route $route, array $data): RouteStop
    {
        $maxOrder = $route->stops()->max('stop_order') ?? 0;

        return RouteStop::create([
            'route_id' => $route->id,
            'city_name' => $data['city_name'],
            'stop_order' => $data['stop_order'] ?? ($maxOrder + 1),
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'distance_from_origin' => $data['distance_from_origin'] ?? null,
        ]);
    }

    public function removeStop(RouteStop $stop): void
    {
        // Cek apakah stop digunakan di schedule
        $isUsedInSchedule = \App\Models\ScheduleStop::where('route_stop_id', $stop->id)->exists();
        
        if ($isUsedInSchedule) {
            throw new \Exception('Stop ini sudah digunakan dalam jadwal, tidak dapat dihapus.');
        }

        // Cek apakah ini stop terakhir - rute harus punya minimal 2 stop
        $routeStopsCount = RouteStop::where('route_id', $stop->route_id)->count();
        if ($routeStopsCount <= 2) {
            throw new \Exception('Rute harus memiliki minimal 2 stop.');
        }

        $stop->delete();
    }
}

// End of file