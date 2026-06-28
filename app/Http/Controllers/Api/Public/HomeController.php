<?php
// File: app/Http/Controllers/Api/Public/HomeController.php
// Deskripsi: API Controller untuk halaman utama public

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Route;
use App\Models\PlatformSetting;
use Illuminate\Http\JsonResponse;

class HomeController extends Controller
{
    public function index(): JsonResponse
    {
        $popularRoutes = Route::where('is_active', true)
            ->withCount(['schedules' => function ($query) {
                $query->where('departure_date', '>=', now()->toDateString())
                    ->where('is_active', true);
            }])
            ->orderByDesc('schedules_count')
            ->limit(5)
            ->get();

        $topAgencies = Agency::where('is_verified', true)
            ->orderByDesc('rating')
            ->orderByDesc('total_bookings')
            ->limit(5)
            ->get();

        $stats = [
            'total_agencies' => Agency::where('is_verified', true)->count(),
            'total_routes' => Route::where('is_active', true)->count(),
            'total_cities' => \App\Models\RouteStop::distinct('city_name')->count('city_name'),
            'app_downloads' => PlatformSetting::getValue('total_app_downloads', 0),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Data homepage berhasil diambil.',
            'data' => [
                'tagline' => config('gomad.tagline', 'Mobilitas orèng Madhurâ'),
                'stats' => $stats,
                'popular_routes' => $popularRoutes->map(function ($route) {
                    return [
                        'id' => $route->id,
                        'route_name' => $route->route_name,
                        'origin_city' => $route->origin_city,
                        'destination_city' => $route->destination_city,
                        'schedules_count' => $route->schedules_count,
                    ];
                }),
                'top_agencies' => $topAgencies->map(function ($agency) {
                    return [
                        'id' => $agency->id,
                        'agency_name' => $agency->agency_name,
                        'slug' => $agency->slug,
                        'logo' => $agency->logo ? asset('storage/' . $agency->logo) : null,
                        'rating' => (float) $agency->rating,
                        'total_bookings' => $agency->total_bookings,
                        'is_verified' => $agency->is_verified,
                    ];
                }),
            ],
            'meta' => null,
        ]);
    }
}

// End of file