<?php

namespace Database\Factories;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        return [
            'booking_code' => 'GM-' . now()->format('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
            'total_passengers' => rand(1, 3),
            'total_price' => rand(100000, 500000),
            'pickup_address' => fake('id_ID')->address(),
            'destination_address' => fake('id_ID')->address(),
            'status' => fake()->randomElement(['pending', 'paid', 'paid', 'paid', 'cancelled']),
        ];
    }

    // State: Paid
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
        ]);
    }

    // State: Pending
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    // State: Cancelled
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }
}