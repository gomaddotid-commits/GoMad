<?php

namespace Database\Factories;

use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition(): array
    {
        $brands = [
            'Toyota' => ['Hiace Commuter', 'Hiace Premio', 'Avanza'],
            'Isuzu' => ['ELF', 'Traga'],
            'Suzuki' => ['APV', 'Ertiga'],
            'Mitsubishi' => ['Xpander', 'L300'],
        ];

        $brand = fake()->randomElement(array_keys($brands));
        $model = fake()->randomElement($brands[$brand]);

        return [
            'plate_number' => 'M ' . rand(1000, 9999) . ' ' . chr(rand(65, 90)) . chr(rand(65, 90)),
            'brand' => $brand,
            'model' => $model,
            'year' => rand(2020, 2024),
            'capacity' => rand(6, 8),
            'type' => fake()->randomElement(['economy', 'premium']),
            'is_active' => true,
        ];
    }

    // State: Economy
    public function economy(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'economy',
            'capacity' => 8,
        ]);
    }

    // State: Premium
    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'premium',
            'capacity' => 6,
        ]);
    }
}