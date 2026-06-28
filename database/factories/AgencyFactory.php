<?php

namespace Database\Factories;

use App\Models\Agency;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgencyFactory extends Factory
{
    protected $model = Agency::class;

    public function definition(): array
    {
        $cities = ['Sumenep', 'Pamekasan', 'Bangkalan', 'Surabaya', 'Malang', 'Jember', 'Probolinggo', 'Jakarta'];

        return [
            'agency_name' => fake('id_ID')->company() . ' Travel',
            'slug' => fake()->unique()->slug(),
            'address' => fake('id_ID')->address(),
            'description' => fake('id_ID')->paragraph(3),
            'founded_year' => rand(2015, 2024),
            'fleet_size' => rand(2, 10),
            'contact_person' => fake('id_ID')->name(),
            'contact_alternate' => '08' . fake()->numerify('##########'),
            'email_alternate' => fake()->email(),
            'services' => json_encode([
                'bagasi_ekstra' => fake()->boolean(60),
                'charger' => fake()->boolean(80),
                'air_mineral' => fake()->boolean(90),
                'wifi' => fake()->boolean(50),
                'selimut' => fake()->boolean(30),
            ]),
            'social_media' => json_encode([
                'facebook' => fake()->boolean(50) ? 'https://facebook.com/' . fake()->slug() : null,
                'instagram' => fake()->boolean(70) ? 'https://instagram.com/' . fake()->slug() : null,
                'tiktok' => fake()->boolean(20) ? 'https://tiktok.com/@' . fake()->slug() : null,
                'youtube' => null,
            ]),
            'business_hours' => json_encode([
                'senin' => '06:00-20:00',
                'selasa' => '06:00-20:00',
                'rabu' => '06:00-20:00',
                'kamis' => '06:00-20:00',
                'jumat' => '06:00-18:00',
                'sabtu' => '06:00-20:00',
                'minggu' => '07:00-18:00',
            ]),
            'zone_coverage' => json_encode(fake()->randomElements($cities, rand(2, 4))),
            'is_verified' => false,
            'rating' => 0,
            'total_bookings' => 0,
        ];
    }

    // State: Verified
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
            'rating' => fake()->randomFloat(1, 3.5, 5.0),
            'total_bookings' => rand(10, 500),
        ]);
    }
}