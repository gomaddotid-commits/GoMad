<?php
// File: database/seeders/RouteSeeder.php
// Deskripsi: Seeder untuk rute dan stops

namespace Database\Seeders;

use App\Models\Route;
use App\Models\RouteStop;
use Illuminate\Database\Seeder;

class RouteSeeder extends Seeder
{
    public function run(): void
    {
        // Rute 1: Sumenep - Surabaya
        $route1 = Route::create([
            'route_name' => 'Sumenep - Surabaya',
            'origin_city' => 'Sumenep',
            'destination_city' => 'Surabaya',
            'distance_km' => 180.00,
            'estimated_duration' => 300,
            'is_active' => true,
        ]);

        $stops1 = [
            ['city_name' => 'Sumenep', 'stop_order' => 1, 'latitude' => -7.0051, 'longitude' => 113.8586, 'distance_from_origin' => 0],
            ['city_name' => 'Pamekasan', 'stop_order' => 2, 'latitude' => -7.1613, 'longitude' => 113.4825, 'distance_from_origin' => 55],
            ['city_name' => 'Bangkalan', 'stop_order' => 3, 'latitude' => -7.0307, 'longitude' => 112.7450, 'distance_from_origin' => 120],
            ['city_name' => 'Surabaya', 'stop_order' => 4, 'latitude' => -7.2575, 'longitude' => 112.7521, 'distance_from_origin' => 180],
        ];

        foreach ($stops1 as $stop) {
            RouteStop::create(array_merge(['route_id' => $route1->id], $stop));
        }

        // Rute 2: Sumenep - Malang
        $route2 = Route::create([
            'route_name' => 'Sumenep - Malang',
            'origin_city' => 'Sumenep',
            'destination_city' => 'Malang',
            'distance_km' => 280.00,
            'estimated_duration' => 420,
            'is_active' => true,
        ]);

        $stops2 = [
            ['city_name' => 'Sumenep', 'stop_order' => 1, 'latitude' => -7.0051, 'longitude' => 113.8586, 'distance_from_origin' => 0],
            ['city_name' => 'Pamekasan', 'stop_order' => 2, 'latitude' => -7.1613, 'longitude' => 113.4825, 'distance_from_origin' => 55],
            ['city_name' => 'Bangkalan', 'stop_order' => 3, 'latitude' => -7.0307, 'longitude' => 112.7450, 'distance_from_origin' => 120],
            ['city_name' => 'Surabaya', 'stop_order' => 4, 'latitude' => -7.2575, 'longitude' => 112.7521, 'distance_from_origin' => 180],
            ['city_name' => 'Malang', 'stop_order' => 5, 'latitude' => -7.9666, 'longitude' => 112.6326, 'distance_from_origin' => 280],
        ];

        foreach ($stops2 as $stop) {
            RouteStop::create(array_merge(['route_id' => $route2->id], $stop));
        }

        // Rute 3: Sumenep - Jember
        $route3 = Route::create([
            'route_name' => 'Sumenep - Jember',
            'origin_city' => 'Sumenep',
            'destination_city' => 'Jember',
            'distance_km' => 230.00,
            'estimated_duration' => 360,
            'is_active' => true,
        ]);

        $stops3 = [
            ['city_name' => 'Sumenep', 'stop_order' => 1, 'latitude' => -7.0051, 'longitude' => 113.8586, 'distance_from_origin' => 0],
            ['city_name' => 'Pamekasan', 'stop_order' => 2, 'latitude' => -7.1613, 'longitude' => 113.4825, 'distance_from_origin' => 55],
            ['city_name' => 'Bangkalan', 'stop_order' => 3, 'latitude' => -7.0307, 'longitude' => 112.7450, 'distance_from_origin' => 120],
            ['city_name' => 'Probolinggo', 'stop_order' => 4, 'latitude' => -7.7535, 'longitude' => 113.2160, 'distance_from_origin' => 170],
            ['city_name' => 'Jember', 'stop_order' => 5, 'latitude' => -8.1845, 'longitude' => 113.6681, 'distance_from_origin' => 230],
        ];

        foreach ($stops3 as $stop) {
            RouteStop::create(array_merge(['route_id' => $route3->id], $stop));
        }

        // Rute 4: Sumenep - Jakarta
        $route4 = Route::create([
            'route_name' => 'Sumenep - Jakarta',
            'origin_city' => 'Sumenep',
            'destination_city' => 'Jakarta',
            'distance_km' => 900.00,
            'estimated_duration' => 1200,
            'is_active' => true,
        ]);

        $stops4 = [
            ['city_name' => 'Sumenep', 'stop_order' => 1, 'latitude' => -7.0051, 'longitude' => 113.8586, 'distance_from_origin' => 0],
            ['city_name' => 'Pamekasan', 'stop_order' => 2, 'latitude' => -7.1613, 'longitude' => 113.4825, 'distance_from_origin' => 55],
            ['city_name' => 'Surabaya', 'stop_order' => 3, 'latitude' => -7.2575, 'longitude' => 112.7521, 'distance_from_origin' => 180],
            ['city_name' => 'Semarang', 'stop_order' => 4, 'latitude' => -6.9932, 'longitude' => 110.4203, 'distance_from_origin' => 450],
            ['city_name' => 'Jakarta', 'stop_order' => 5, 'latitude' => -6.2088, 'longitude' => 106.8456, 'distance_from_origin' => 900],
        ];

        foreach ($stops4 as $stop) {
            RouteStop::create(array_merge(['route_id' => $route4->id], $stop));
        }
    }
}

// End of file