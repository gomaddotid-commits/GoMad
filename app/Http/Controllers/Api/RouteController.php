<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Route;
use Illuminate\Http\JsonResponse;

class RouteController extends Controller
{
    /**
     * Dapatkan rute kebalikan untuk PP
     */
    public function returnRoute(Route $route): JsonResponse
    {
        // Cari rute kebalikan yang sudah ada
        $returnRoute = Route::where('origin_city_code', $route->destination_city_code)
            ->where('destination_city_code', $route->origin_city_code)
            ->where('is_active', true)
            ->first();

        if ($returnRoute) {
            // Rute PP sudah ada — kembalikan data stop-nya
            $stops = $returnRoute->stops()
                ->orderBy('stop_order')
                ->get()
                ->map(function ($stop) {
                    return [
                        'id' => $stop->id,
                        'city_code' => $stop->city_code,
                        'city_name' => $stop->city_name,
                        'stop_order' => $stop->stop_order,
                        'is_first' => $stop->isFirst(),
                        'is_last' => $stop->isLast(),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'exists' => true,
                    'id' => $returnRoute->id,
                    'route_name' => $returnRoute->route_name,
                    'origin_city' => $returnRoute->origin_city_name,
                    'destination_city' => $returnRoute->destination_city_name,
                    'estimated_duration' => $returnRoute->estimated_duration,
                    'stops' => $stops->values(),
                ],
            ]);
        }

        // Rute PP belum ada — kembalikan preview (TERBALIK)
        $originalStops = $route->stops()->orderBy('stop_order')->get();
        $reversedStops = $originalStops->reverse()->values();
        $totalStops = $reversedStops->count();

        $stops = $reversedStops->map(function ($stop, $index) use ($totalStops) {
            return [
                'id' => null,
                'city_code' => $stop->city_code,
                'city_name' => $stop->city_name,
                'stop_order' => $index + 1,
                'is_first' => $index === 0,
                'is_last' => $index === $totalStops - 1,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'exists' => false,
                'id' => null,
                'route_name' => $route->destination_city_name . ' - ' . $route->origin_city_name,
                'origin_city' => $route->destination_city_name,
                'destination_city' => $route->origin_city_name,
                'estimated_duration' => $route->estimated_duration,
                'stops' => $stops->values(),
            ],
        ]);
    }
}