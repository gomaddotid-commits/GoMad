<?php
// File: app/Http/Controllers/Api/Public/SearchController.php
// Deskripsi: API Controller untuk pencarian jadwal

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ScheduleResource;
use App\Models\RouteStop;
use App\Services\RouteService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(
        private readonly RouteService $routeService,
    ) {}

    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'origin' => ['required', 'string', 'max:100'],
            'destination' => ['required', 'string', 'max:100'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'travel_class' => ['nullable', 'in:economy,premium,charter'],
            'min_seats' => ['nullable', 'integer', 'min:1'],
            'agency_id' => ['nullable', 'integer', 'exists:agencies,id'],
        ]);

        $date = Carbon::parse($request->date);
        $filters = $request->only(['travel_class', 'min_seats', 'agency_id']);

        $schedules = $this->routeService->searchSchedules(
            $request->origin,
            $request->destination,
            $date,
            $filters
        );

        return response()->json([
            'success' => true,
            'message' => 'Hasil pencarian jadwal.',
            'data' => [
                'schedules' => ScheduleResource::collection($schedules),
                'total' => $schedules->count(),
                'filters' => [
                    'origin' => $request->origin,
                    'destination' => $request->destination,
                    'date' => $request->date,
                ],
            ],
            'meta' => null,
        ]);
    }

    public function schedules(Request $request): JsonResponse
    {
        $request->validate([
            'origin_city' => ['nullable', 'string'],
            'destination_city' => ['nullable', 'string'],
            'date' => ['nullable', 'date'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = \App\Models\Schedule::with(['route', 'agency', 'vehicle'])
            ->where('is_active', true)
            ->where('departure_date', '>=', now()->toDateString());

        if ($request->origin_city) {
            $query->whereHas('route', function ($q) use ($request) {
                $q->where('origin_city', 'like', '%' . $request->origin_city . '%');
            });
        }

        if ($request->destination_city) {
            $query->whereHas('route', function ($q) use ($request) {
                $q->where('destination_city', 'like', '%' . $request->destination_city . '%');
            });
        }

        if ($request->date) {
            $query->where('departure_date', $request->date);
        }

        $schedules = $query->orderBy('departure_date')
            ->orderBy('departure_time')
            ->limit($request->limit ?? 20)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar jadwal berhasil diambil.',
            'data' => ScheduleResource::collection($schedules),
            'meta' => [
                'total' => $schedules->count(),
            ],
        ]);
    }

    public function scheduleDetail(int $id): JsonResponse
    {
        $schedule = \App\Models\Schedule::with([
            'route.stops',
            'agency',
            'vehicle',
            'scheduleStops.routeStop',
            'routePricing.originStop',
            'routePricing.destinationStop',
        ])->findOrFail($id);

        $scheduleService = app(\App\Services\ScheduleService::class);
        $scheduleData = $scheduleService->getScheduleWithPricing($schedule);

        return response()->json([
            'success' => true,
            'message' => 'Detail jadwal berhasil diambil.',
            'data' => $scheduleData,
            'meta' => null,
        ]);
    }

    public function cities(): JsonResponse
    {
        $cities = $this->routeService->getAllCities();

        return response()->json([
            'success' => true,
            'message' => 'Daftar kota berhasil diambil.',
            'data' => $cities,
            'meta' => [
                'total' => $cities->count(),
            ],
        ]);
    }

    public function routes(): JsonResponse
    {
        $routes = $this->routeService->getAllRoutes();

        return response()->json([
            'success' => true,
            'message' => 'Daftar rute berhasil diambil.',
            'data' => $routes,
            'meta' => [
                'total' => $routes->count(),
            ],
        ]);
    }

    public function nearbyWarungs(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['nullable', 'numeric', 'min:1', 'max:9999'],
        ]);

        $agentService = app(\App\Services\PaymentAgentService::class);
        $agents = $agentService->getNearbyAgents(
            (float) $request->latitude,
            (float) $request->longitude,
            (float) ($request->radius ?? 10)
        );

        return response()->json([
            'success' => true,
            'message' => 'Daftar warung terdekat berhasil diambil.',
            'data' => \App\Http\Resources\Api\PaymentAgentResource::collection($agents),
            'meta' => [
                'total' => $agents->count(),
                'center' => [
                    'latitude' => (float) $request->latitude,
                    'longitude' => (float) $request->longitude,
                ],
            ],
        ]);
    }

    public function routeDetail($id): JsonResponse
    {
        $route = \App\Models\Route::findOrFail($id);
        
        return response()->json([
            'success' => true,
            'message' => 'Detail rute.',
            'data' => [
                'id' => $route->id,
                'cod_available' => $route->cod_available,
                'cod_min_deposit' => (float) $route->cod_min_deposit,
                'max_price' => (float) $route->max_price,
            ],
            'meta' => null,
        ]);
    }
}

// End of file