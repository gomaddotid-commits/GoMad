<?php
// File: app/Http/Controllers/Api/Driver/RouteController.php
// Deskripsi: API Controller untuk rute driver

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RouteController extends Controller
{
    public function routeDetail(Request $request, Schedule $schedule): JsonResponse
    {
        $driver = $request->user();

        if ($schedule->driver_id !== $driver->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak ditugaskan di jadwal ini.',
                'data' => null,
                'meta' => null,
            ], 403);
        }

        $schedule->load(['route.stops', 'scheduleStops.routeStop']);

        $stops = $schedule->scheduleStops()->with('routeStop')->get()->sortBy(function ($ss) {
            return $ss->routeStop->stop_order;
        });

        $routeData = [
            'schedule_id' => $schedule->id,
            'route_name' => $schedule->route->route_name,
            'origin_city' => $schedule->route->origin_city,
            'destination_city' => $schedule->route->destination_city,
            'stops' => $stops->map(function ($ss) {
                return [
                    'id' => $ss->id,
                    'city_name' => $ss->routeStop->city_name,
                    'stop_order' => $ss->routeStop->stop_order,
                    'latitude' => $ss->routeStop->latitude ? (float) $ss->routeStop->latitude : null,
                    'longitude' => $ss->routeStop->longitude ? (float) $ss->routeStop->longitude : null,
                    'is_pickup_available' => $ss->is_pickup_available,
                    'is_dropoff_available' => $ss->is_dropoff_available,
                    'estimated_time' => $ss->estimated_time,
                ];
            })->values(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Detail rute berhasil diambil.',
            'data' => $routeData,
            'meta' => null,
        ]);
    }
}

// End of file