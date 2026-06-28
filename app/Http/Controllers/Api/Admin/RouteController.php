<?php
// File: app/Http/Controllers/Api/Admin/RouteController.php
// Deskripsi: API Controller untuk manajemen rute oleh admin

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\RouteResource;
use App\Models\Route;
use App\Services\RouteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RouteController extends Controller
{
    public function __construct(
        private readonly RouteService $routeService,
    ) {}

    public function index(): JsonResponse
    {
        $routes = $this->routeService->getAllRoutes();

        return response()->json([
            'success' => true,
            'message' => 'Daftar rute berhasil diambil.',
            'data' => RouteResource::collection($routes),
            'meta' => ['total' => $routes->count()],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'route_name' => ['required', 'string', 'max:100'],
            'origin_city' => ['required', 'string', 'max:100'],
            'destination_city' => ['required', 'string', 'max:100', 'different:origin_city'],
            'distance_km' => ['nullable', 'numeric', 'min:0'],
            'estimated_duration' => ['nullable', 'integer', 'min:0'],
            'stops' => ['required', 'array', 'min:2'],
            'stops.*.city_name' => ['required', 'string', 'max:100'],
            'stops.*.stop_order' => ['nullable', 'integer', 'min:1'],
            'stops.*.latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'stops.*.longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'stops.*.distance_from_origin' => ['nullable', 'numeric', 'min:0'],
        ]);

        $route = $this->routeService->createRoute($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Rute berhasil dibuat.',
            'data' => new RouteResource($route),
            'meta' => null,
        ], 201);
    }

    public function show(Route $route): JsonResponse
    {
        $route->load('stops');

        return response()->json([
            'success' => true,
            'message' => 'Detail rute berhasil diambil.',
            'data' => new RouteResource($route),
            'meta' => null,
        ]);
    }

    public function update(Request $request, Route $route): JsonResponse
    {
        $request->validate([
            'route_name' => ['sometimes', 'string', 'max:100'],
            'origin_city' => ['sometimes', 'string', 'max:100'],
            'destination_city' => ['sometimes', 'string', 'max:100'],
            'distance_km' => ['nullable', 'numeric', 'min:0'],
            'estimated_duration' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $route = $this->routeService->updateRoute($route, $request->all());

        return response()->json([
            'success' => true,
            'message' => 'Rute berhasil diupdate.',
            'data' => new RouteResource($route),
            'meta' => null,
        ]);
    }

    public function destroy(Route $route): JsonResponse
    {
        $hasSchedules = $route->schedules()->exists();
        if ($hasSchedules) {
            return response()->json([
                'success' => false,
                'message' => 'Rute memiliki jadwal, tidak dapat dihapus.',
                'data' => null,
                'meta' => null,
            ], 422);
        }

        $route->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Rute berhasil dinonaktifkan.',
            'data' => null,
            'meta' => null,
        ]);
    }

    public function addStop(Request $request, Route $route): JsonResponse
    {
        $request->validate([
            'city_name' => ['required', 'string', 'max:100'],
            'stop_order' => ['nullable', 'integer', 'min:1'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'distance_from_origin' => ['nullable', 'numeric', 'min:0'],
        ]);

        $stop = $this->routeService->addStop($route, $request->all());

        return response()->json([
            'success' => true,
            'message' => 'Stop berhasil ditambahkan.',
            'data' => [
                'id' => $stop->id,
                'city_name' => $stop->city_name,
                'stop_order' => $stop->stop_order,
            ],
            'meta' => null,
        ], 201);
    }

    public function removeStop(Route $route, $stop): JsonResponse
    {
        $stop = \App\Models\RouteStop::where('route_id', $route->id)->findOrFail($stop);

        try {
            $this->routeService->removeStop($stop);

            return response()->json([
                'success' => true,
                'message' => 'Stop berhasil dihapus.',
                'data' => null,
                'meta' => null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
                'meta' => null,
            ], 422);
        }
    }
}

// End of file