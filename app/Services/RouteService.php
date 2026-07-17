<?php

namespace App\Services;

use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Schedule;
use App\Models\City;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class RouteService
{
    /**
     * Hitung jarak antara dua titik (Haversine formula)
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }

    /**
     * Dapatkan kota yang tersedia untuk stop di antara origin & destination
     */
    public function getAvailableStops(string $originCityCode, string $destCityCode): Collection
    {
        $origin = City::findOrFail($originCityCode);
        $destination = City::findOrFail($destCityCode);

        $originLat = $origin->latitude ?? 0;
        $originLng = $origin->longitude ?? 0;
        $destLat = $destination->latitude ?? 0;
        $destLng = $destination->longitude ?? 0;

        // Hitung bounding box dengan margin
        $margin = 0.5; // ~55 km
        $minLat = min($originLat, $destLat) - $margin;
        $maxLat = max($originLat, $destLat) + $margin;
        $minLng = min($originLng, $destLng) - $margin;
        $maxLng = max($originLng, $destLng) + $margin;

        // Hitung jarak maksimum rute + margin
        $maxDistance = $this->calculateDistance($originLat, $originLng, $destLat, $destLng) * 1.5;

        return City::with('province')
            ->where('code', '!=', $originCityCode)
            ->where('code', '!=', $destCityCode)
            ->orderBy('name')
            ->get()
            ->filter(function ($city) use ($originLat, $originLng, $minLat, $maxLat, $minLng, $maxLng, $maxDistance) {
                $lat = $city->latitude ?? 0;
                $lng = $city->longitude ?? 0;

                if ($lat == 0 && $lng == 0) return false;

                if ($lat < $minLat || $lat > $maxLat || $lng < $minLng || $lng > $maxLng) {
                    return false;
                }

                $distFromOrigin = $this->calculateDistance($originLat, $originLng, $lat, $lng);
                if ($distFromOrigin > $maxDistance) return false;

                return true;
            })
            ->map(function ($city) use ($originLat, $originLng) {
                $city->distance_from_origin = $this->calculateDistance(
                    $originLat, $originLng,
                    $city->latitude ?? 0, $city->longitude ?? 0
                );
                return $city;
            })
            ->sortBy('distance_from_origin')
            ->values();
    }

    /**
     * Buat rute baru dengan auto-calculate distance & duration
     */
    public function createRoute(array $data): Route
    {
        $origin = City::findOrFail($data['origin_city_code']);
        $destination = City::findOrFail($data['destination_city_code']);

        // Auto-calculate distance & duration
        $distance = $this->calculateDistance(
            $origin->latitude ?? 0, $origin->longitude ?? 0,
            $destination->latitude ?? 0, $destination->longitude ?? 0
        );
        $estimatedDuration = round(($distance / 50) * 60);

        $route = Route::create([
            'route_name' => $data['route_name'] ?? "{$origin->name} - {$destination->name}",
            'origin_city_code' => $origin->code,
            'destination_city_code' => $destination->code,
            'distance_km' => $distance,
            'estimated_duration' => $estimatedDuration,
            'max_price' => $data['max_price'] ?? null,
            'cod_available' => $data['cod_available'] ?? false,
            'cod_min_deposit' => $data['cod_min_deposit'] ?? 500000,
            'payment_methods' => $data['payment_methods'] ?? null,
            'description' => $data['description'] ?? null,
            'photo' => $data['photo'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        // Stop 1: Origin (wajib)
        RouteStop::create([
            'route_id' => $route->id,
            'city_code' => $origin->code,
            'stop_order' => 1,
            'latitude' => $origin->latitude,
            'longitude' => $origin->longitude,
            'distance_from_origin' => 0,
        ]);

        // Stop 2..n-1: Intermediate stops
        foreach ($data['stop_city_codes'] ?? [] as $cityCode) {
            $city = City::findOrFail($cityCode);
            $distFromOrigin = $this->calculateDistance(
                $origin->latitude ?? 0, $origin->longitude ?? 0,
                $city->latitude ?? 0, $city->longitude ?? 0
            );

            RouteStop::create([
                'route_id' => $route->id,
                'city_code' => $cityCode,
                'stop_order' => 0, // Sementara, akan di-reorder
                'latitude' => $city->latitude,
                'longitude' => $city->longitude,
                'distance_from_origin' => $distFromOrigin,
            ]);
        }

        // Stop terakhir: Destination (wajib)
        RouteStop::create([
            'route_id' => $route->id,
            'city_code' => $destination->code,
            'stop_order' => 999, // Sementara, akan di-reorder
            'latitude' => $destination->latitude,
            'longitude' => $destination->longitude,
            'distance_from_origin' => $distance,
        ]);

        // Reorder semua stop berdasarkan jarak
        $this->reorderStopsByDistance($route);

        Log::info('Route created', ['route_id' => $route->id, 'stops_count' => $route->stops()->count()]);

        return $route->load(['stops.city', 'originCity', 'destinationCity']);
    }

    /**
     * Update rute
     */
    public function updateRoute(Route $route, array $data): Route
    {
        Log::info('Updating route', ['route_id' => $route->id, 'data' => $data]);

        $updateData = [];

        // Field yang boleh diupdate
        $allowedFields = [
            'route_name', 'max_price', 'cod_available', 'cod_min_deposit',
            'payment_methods', 'description', 'photo', 'is_active'
        ];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }

        // Update origin & destination jika berubah
        if (!empty($data['origin_city_code'])) {
            $updateData['origin_city_code'] = $data['origin_city_code'];
        }
        if (!empty($data['destination_city_code'])) {
            $updateData['destination_city_code'] = $data['destination_city_code'];
        }

        // Recalculate distance jika origin/destination berubah
        if (isset($updateData['origin_city_code']) || isset($updateData['destination_city_code'])) {
            $origin = City::find($updateData['origin_city_code'] ?? $route->origin_city_code);
            $destination = City::find($updateData['destination_city_code'] ?? $route->destination_city_code);
            
            if ($origin && $destination) {
                $updateData['distance_km'] = $this->calculateDistance(
                    $origin->latitude ?? 0, $origin->longitude ?? 0,
                    $destination->latitude ?? 0, $destination->longitude ?? 0
                );
                $updateData['estimated_duration'] = round(($updateData['distance_km'] / 50) * 60);
            }
        }

        if (!empty($updateData)) {
            $route->update($updateData);
            Log::info('Route updated successfully', [
                'route_id' => $route->id, 
                'fields' => array_keys($updateData)
            ]);
        } else {
            Log::warning('No fields to update', ['route_id' => $route->id]);
        }

        return $route->fresh()->load(['stops.city', 'originCity', 'destinationCity']);
    }

    /**
     * Tambah stop ke rute
     */
    public function addStop(Route $route, array $data): RouteStop
    {
        $city = City::findOrFail($data['city_code']);
        $origin = $route->originCity;

        Log::info('Adding stop', [
            'route_id' => $route->id,
            'city_code' => $data['city_code'],
            'city_name' => $city->name,
        ]);

        // Cek apakah city_code sudah ada di rute ini
        $exists = RouteStop::where('route_id', $route->id)
            ->where('city_code', $data['city_code'])
            ->exists();

        if ($exists) {
            throw new \Exception("Kota {$city->name} sudah ada di rute ini.");
        }

        // Hitung jarak dari origin
        $distFromOrigin = $this->calculateDistance(
            $origin->latitude ?? 0, $origin->longitude ?? 0,
            $city->latitude ?? 0, $city->longitude ?? 0
        );

        // Buat stop baru dengan stop_order sementara (0)
        $stop = RouteStop::create([
            'route_id' => $route->id,
            'city_code' => $city->code,
            'stop_order' => 0, // Sementara, akan di-reorder
            'latitude' => $city->latitude,
            'longitude' => $city->longitude,
            'distance_from_origin' => $distFromOrigin,
        ]);

        // Reorder semua stop berdasarkan jarak dari origin
        $this->reorderStopsByDistance($route);

        Log::info('Stop added & reordered', [
            'stop_id' => $stop->id,
            'city_name' => $city->name,
            'distance' => $distFromOrigin,
        ]);

        return $stop->fresh();
    }

    /**
     * Hapus stop dari rute
     */
    public function removeStop(RouteStop $stop): void
    {
        $routeId = $stop->route_id;
        $cityName = $stop->city_name;
        $deletedOrder = $stop->stop_order;

        $isUsedInSchedule = \App\Models\ScheduleStop::where('route_stop_id', $stop->id)->exists();

        if ($isUsedInSchedule) {
            throw new \Exception("Stop {$cityName} sudah digunakan dalam jadwal, tidak dapat dihapus.");
        }

        $routeStopsCount = RouteStop::where('route_id', $routeId)->count();
        if ($routeStopsCount <= 2) {
            throw new \Exception('Rute harus memiliki minimal 2 stop.');
        }

        // Hapus stop
        $stop->delete();

        Log::info('Stop removed, reordering...', [
            'route_id' => $routeId,
            'deleted_order' => $deletedOrder,
        ]);

        // Reorder ulang berdasarkan jarak
        $route = Route::find($routeId);
        if ($route) {
            $this->reorderStopsByDistance($route);
        }

        Log::info('Stop removed successfully', [
            'route_id' => $routeId,
            'city_name' => $cityName,
        ]);
    }

    /**
     * Reorder semua stop berdasarkan jarak dari origin
     * Origin tetap #1, Destination tetap terakhir
     */
    private function reorderStopsByDistance(Route $route): void
    {
        $allStops = $route->stops()->orderBy('stop_order')->get();
        
        if ($allStops->count() < 2) return;
        
        // Origin (jarak = 0)
        $originStop = $allStops->first(function($stop) {
            return (float) $stop->distance_from_origin === 0.0;
        });
        
        // Destination (jarak terjauh)
        $destinationStop = $allStops->sortByDesc('distance_from_origin')->first();
        
        // Stop tengah (intermediate) — exclude origin & destination
        $intermediateStops = $allStops->filter(function($stop) use ($originStop, $destinationStop) {
            return $stop->id !== $originStop->id && $stop->id !== $destinationStop->id;
        });
        
        // Sort intermediate stops by distance_from_origin
        $sortedIntermediate = $intermediateStops->sortBy('distance_from_origin')->values();
        
        // Reorder: origin = 1, intermediate = 2,3,4..., destination = last
        $order = 1;
        
        // Origin
        $originStop->update(['stop_order' => $order++]);
        
        // Intermediate stops (sorted by distance)
        foreach ($sortedIntermediate as $s) {
            $s->update(['stop_order' => $order++]);
        }
        
        // Destination
        $destinationStop->update(['stop_order' => $order]);
        
        Log::info('Stops reordered by distance', [
            'route_id' => $route->id,
            'total_stops' => $order,
            'order' => $route->stops()->orderBy('stop_order')->get()->pluck('city_name', 'stop_order')->toArray(),
        ]);
    }

    /**
     * Pencarian jadwal
     */
    public function searchSchedules(string $origin, string $destination, Carbon $date, ?array $filters = []): Collection
    {
        $query = Schedule::with([
            'route.stops',
            'agency',
            'vehicle',
            'scheduleStops.routeStop',
            'routePricing',
        ])
        ->where('is_active', true)
        ->where('departure_date', $date->toDateString());

        if (!empty($filters['origin_city_code'])) {
            $query->whereHas('route.stops', function ($q) use ($filters) {
                $q->where('city_code', $filters['origin_city_code']);
            });
        } else {
            $query->whereHas('route.stops', function ($q) use ($origin) {
                $q->whereHas('city', function ($sq) use ($origin) {
                    $sq->where('name', 'like', "%{$origin}%");
                });
            });
        }

        if (!empty($filters['destination_city_code'])) {
            $query->whereHas('route.stops', function ($q) use ($filters) {
                $q->where('city_code', $filters['destination_city_code']);
            });
        } else {
            $query->whereHas('route.stops', function ($q) use ($destination) {
                $q->whereHas('city', function ($sq) use ($destination) {
                    $sq->where('name', 'like', "%{$destination}%");
                });
            });
        }

        if (!empty($filters['travel_class'])) {
            $query->where('travel_class', $filters['travel_class']);
        }

        if (!empty($filters['agency_id'])) {
            $query->where('agency_id', $filters['agency_id']);
        }

        if (!empty($filters['city_code'])) {
            $query->whereHas('agency', function ($q) use ($filters) {
                $q->where('city_code', $filters['city_code']);
            });
        }

        return $query->orderBy('departure_time')->get();
    }

    /**
     * Dapatkan semua kota (dari Laravolt)
     */
    public function getAllCities(): Collection
    {
        return City::with('province')
            ->orderBy('name')
            ->get()
            ->map(fn($city) => [
                'code' => $city->code,
                'name' => $city->name,
                'province' => $city->province?->name,
                'latitude' => $city->latitude,
                'longitude' => $city->longitude,
            ]);
    }

    /**
     * Dapatkan semua rute
     */
    public function getAllRoutes(): Collection
    {
        return Route::with(['stops.city', 'originCity', 'destinationCity'])
            ->where('is_active', true)
            ->orderBy('route_name')
            ->get();
    }

    /**
     * Dapatkan rute populer
     */
    public function getPopularRoutes(int $limit = 5): Collection
    {
        return Route::withCount(['schedules' => function ($query) {
            $query->where('departure_date', '>=', now()->toDateString())
                ->where('is_active', true);
        }])
        ->with(['originCity', 'destinationCity'])
        ->where('is_active', true)
        ->orderByDesc('schedules_count')
        ->limit($limit)
        ->get();
    }
}