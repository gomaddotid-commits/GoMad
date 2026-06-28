<?php
// File: database/seeders/AdditionalRouteSeeder.php
// Deskripsi: Seeder untuk route tambahan (variasi keberangkatan)

namespace Database\Seeders;

use App\Models\Route;
use App\Models\RouteStop;
use Illuminate\Database\Seeder;

class AdditionalRouteSeeder extends Seeder
{
    public function run(): void
    {
        echo "🗺️  GENERATING ADDITIONAL ROUTES...\n";
        echo "═══════════════════════════════════════════\n\n";

        $newRoutes = [
            // ========================================
            // Rute 5: Surabaya - Malang
            // ========================================
            [
                'route' => [
                    'route_name' => 'Surabaya - Malang',
                    'origin_city' => 'Surabaya',
                    'destination_city' => 'Malang',
                    'distance_km' => 100.00,
                    'estimated_duration' => 120,
                    'is_active' => true,
                    'cod_available' => true,
                    'cod_min_deposit' => 400000,
                ],
                'stops' => [
                    ['city_name' => 'Surabaya', 'stop_order' => 1, 'latitude' => -7.2575, 'longitude' => 112.7521, 'distance_from_origin' => 0],
                    ['city_name' => 'Sidoarjo', 'stop_order' => 2, 'latitude' => -7.4498, 'longitude' => 112.7202, 'distance_from_origin' => 30],
                    ['city_name' => 'Pasuruan', 'stop_order' => 3, 'latitude' => -7.6346, 'longitude' => 112.9028, 'distance_from_origin' => 60],
                    ['city_name' => 'Malang', 'stop_order' => 4, 'latitude' => -7.9666, 'longitude' => 112.6326, 'distance_from_origin' => 100],
                ],
            ],
            // ========================================
            // Rute 6: Surabaya - Jember
            // ========================================
            [
                'route' => [
                    'route_name' => 'Surabaya - Jember',
                    'origin_city' => 'Surabaya',
                    'destination_city' => 'Jember',
                    'distance_km' => 200.00,
                    'estimated_duration' => 210,
                    'is_active' => true,
                    'cod_available' => true,
                    'cod_min_deposit' => 500000,
                ],
                'stops' => [
                    ['city_name' => 'Surabaya', 'stop_order' => 1, 'latitude' => -7.2575, 'longitude' => 112.7521, 'distance_from_origin' => 0],
                    ['city_name' => 'Pasuruan', 'stop_order' => 2, 'latitude' => -7.6346, 'longitude' => 112.9028, 'distance_from_origin' => 65],
                    ['city_name' => 'Probolinggo', 'stop_order' => 3, 'latitude' => -7.7535, 'longitude' => 113.2160, 'distance_from_origin' => 105],
                    ['city_name' => 'Jember', 'stop_order' => 4, 'latitude' => -8.1845, 'longitude' => 113.6681, 'distance_from_origin' => 200],
                ],
            ],
            // ========================================
            // Rute 7: Surabaya - Jakarta
            // ========================================
            [
                'route' => [
                    'route_name' => 'Surabaya - Jakarta',
                    'origin_city' => 'Surabaya',
                    'destination_city' => 'Jakarta',
                    'distance_km' => 800.00,
                    'estimated_duration' => 720,
                    'is_active' => true,
                    'cod_available' => true,
                    'cod_min_deposit' => 1000000,
                ],
                'stops' => [
                    ['city_name' => 'Surabaya', 'stop_order' => 1, 'latitude' => -7.2575, 'longitude' => 112.7521, 'distance_from_origin' => 0],
                    ['city_name' => 'Semarang', 'stop_order' => 2, 'latitude' => -6.9932, 'longitude' => 110.4203, 'distance_from_origin' => 320],
                    ['city_name' => 'Cirebon', 'stop_order' => 3, 'latitude' => -6.7320, 'longitude' => 108.5543, 'distance_from_origin' => 500],
                    ['city_name' => 'Jakarta', 'stop_order' => 4, 'latitude' => -6.2088, 'longitude' => 106.8456, 'distance_from_origin' => 800],
                ],
            ],
            // ========================================
            // Rute 8: Malang - Jember
            // ========================================
            [
                'route' => [
                    'route_name' => 'Malang - Jember',
                    'origin_city' => 'Malang',
                    'destination_city' => 'Jember',
                    'distance_km' => 150.00,
                    'estimated_duration' => 180,
                    'is_active' => true,
                    'cod_available' => true,
                    'cod_min_deposit' => 400000,
                ],
                'stops' => [
                    ['city_name' => 'Malang', 'stop_order' => 1, 'latitude' => -7.9666, 'longitude' => 112.6326, 'distance_from_origin' => 0],
                    ['city_name' => 'Lumajang', 'stop_order' => 2, 'latitude' => -8.1195, 'longitude' => 113.2168, 'distance_from_origin' => 85],
                    ['city_name' => 'Jember', 'stop_order' => 3, 'latitude' => -8.1845, 'longitude' => 113.6681, 'distance_from_origin' => 150],
                ],
            ],
            // ========================================
            // Rute 9: Malang - Jakarta
            // ========================================
            [
                'route' => [
                    'route_name' => 'Malang - Jakarta',
                    'origin_city' => 'Malang',
                    'destination_city' => 'Jakarta',
                    'distance_km' => 850.00,
                    'estimated_duration' => 780,
                    'is_active' => true,
                    'cod_available' => true,
                    'cod_min_deposit' => 1000000,
                ],
                'stops' => [
                    ['city_name' => 'Malang', 'stop_order' => 1, 'latitude' => -7.9666, 'longitude' => 112.6326, 'distance_from_origin' => 0],
                    ['city_name' => 'Surabaya', 'stop_order' => 2, 'latitude' => -7.2575, 'longitude' => 112.7521, 'distance_from_origin' => 100],
                    ['city_name' => 'Semarang', 'stop_order' => 3, 'latitude' => -6.9932, 'longitude' => 110.4203, 'distance_from_origin' => 420],
                    ['city_name' => 'Jakarta', 'stop_order' => 4, 'latitude' => -6.2088, 'longitude' => 106.8456, 'distance_from_origin' => 850],
                ],
            ],
            // ========================================
            // Rute 10: Pamekasan - Surabaya
            // ========================================
            [
                'route' => [
                    'route_name' => 'Pamekasan - Surabaya',
                    'origin_city' => 'Pamekasan',
                    'destination_city' => 'Surabaya',
                    'distance_km' => 130.00,
                    'estimated_duration' => 150,
                    'is_active' => true,
                    'cod_available' => true,
                    'cod_min_deposit' => 400000,
                ],
                'stops' => [
                    ['city_name' => 'Pamekasan', 'stop_order' => 1, 'latitude' => -7.1613, 'longitude' => 113.4825, 'distance_from_origin' => 0],
                    ['city_name' => 'Bangkalan', 'stop_order' => 2, 'latitude' => -7.0307, 'longitude' => 112.7450, 'distance_from_origin' => 65],
                    ['city_name' => 'Surabaya', 'stop_order' => 3, 'latitude' => -7.2575, 'longitude' => 112.7521, 'distance_from_origin' => 130],
                ],
            ],
            // ========================================
            // Rute 11: Bangkalan - Malang
            // ========================================
            [
                'route' => [
                    'route_name' => 'Bangkalan - Malang',
                    'origin_city' => 'Bangkalan',
                    'destination_city' => 'Malang',
                    'distance_km' => 160.00,
                    'estimated_duration' => 180,
                    'is_active' => true,
                    'cod_available' => true,
                    'cod_min_deposit' => 450000,
                ],
                'stops' => [
                    ['city_name' => 'Bangkalan', 'stop_order' => 1, 'latitude' => -7.0307, 'longitude' => 112.7450, 'distance_from_origin' => 0],
                    ['city_name' => 'Surabaya', 'stop_order' => 2, 'latitude' => -7.2575, 'longitude' => 112.7521, 'distance_from_origin' => 60],
                    ['city_name' => 'Malang', 'stop_order' => 3, 'latitude' => -7.9666, 'longitude' => 112.6326, 'distance_from_origin' => 160],
                ],
            ],
            // ========================================
            // Rute 12: Sumenep - Probolinggo
            // ========================================
            [
                'route' => [
                    'route_name' => 'Sumenep - Probolinggo',
                    'origin_city' => 'Sumenep',
                    'destination_city' => 'Probolinggo',
                    'distance_km' => 170.00,
                    'estimated_duration' => 210,
                    'is_active' => true,
                    'cod_available' => true,
                    'cod_min_deposit' => 450000,
                ],
                'stops' => [
                    ['city_name' => 'Sumenep', 'stop_order' => 1, 'latitude' => -7.0051, 'longitude' => 113.8586, 'distance_from_origin' => 0],
                    ['city_name' => 'Pamekasan', 'stop_order' => 2, 'latitude' => -7.1613, 'longitude' => 113.4825, 'distance_from_origin' => 55],
                    ['city_name' => 'Probolinggo', 'stop_order' => 3, 'latitude' => -7.7535, 'longitude' => 113.2160, 'distance_from_origin' => 170],
                ],
            ],
        ];

        $totalRoutes = 0;

        foreach ($newRoutes as $data) {
            // Cek apakah route sudah ada
            $exists = Route::where('route_name', $data['route']['route_name'])->exists();
            if ($exists) {
                echo "  ⏭️  {$data['route']['route_name']} sudah ada, skip...\n";
                continue;
            }

            $route = Route::create($data['route']);

            foreach ($data['stops'] as $stop) {
                RouteStop::create(array_merge(['route_id' => $route->id], $stop));
            }

            $stopCount = count($data['stops']);
            echo "  ✅ {$data['route']['route_name']} ({$data['route']['distance_km']} km, {$stopCount} stops)\n";
            $totalRoutes++;
        }

        echo "\n═══════════════════════════════════════════\n";
        echo "✅ {$totalRoutes} ROUTES BARU DITAMBAHKAN!\n";
        echo "═══════════════════════════════════════════\n";
        echo "🗺️  Total Routes: " . Route::count() . "\n\n";

        echo "📊 ROUTE LIST:\n";
        echo "──────────────────────────────────────────────\n";
        foreach (Route::all() as $route) {
            $stopCount = $route->stops()->count();
            echo "  • {$route->route_name} ({$route->distance_km} km, {$stopCount} stops)\n";
        }
        echo "──────────────────────────────────────────────\n";
    }
}