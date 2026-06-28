<?php

namespace Database\Factories;

use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduleFactory extends Factory
{
    protected $model = Schedule::class;

    public function definition(): array
    {
        $class = fake()->randomElement(['economy', 'premium']);
        
        return [
            'departure_date' => Carbon::now()->addDays(rand(1, 30))->toDateString(),
            'departure_time' => sprintf('%02d:%02d', rand(5, 20), rand(0, 11) * 5),
            'travel_class' => $class,
            'max_overload' => $class === 'premium' ? 0 : rand(0, 3),
            'price_per_seat' => rand(100000, 400000),
            'baggage_limit_kg' => $class === 'premium' ? 20.00 : 15.00,
            'is_active' => true,
            'allow_passenger_transfer' => fake()->boolean(70),
            'accept_external_transfer' => fake()->boolean(50),
            'transfer_fee_per_passenger' => rand(15000, 30000),
            'allow_cod' => $class === 'economy' && fake()->boolean(60),
            'cod_min_balance' => 500000,
            'started_at' => null,
            'finished_at' => null,
        ];
    }

    // State: Economy
    public function economy(): static
    {
        return $this->state(fn (array $attributes) => [
            'travel_class' => 'economy',
            'price_per_seat' => rand(100000, 250000),
            'baggage_limit_kg' => 15.00,
        ]);
    }

    // State: Premium
    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'travel_class' => 'premium',
            'price_per_seat' => rand(200000, 400000),
            'baggage_limit_kg' => 20.00,
            'max_overload' => 0,
        ]);
    }
}