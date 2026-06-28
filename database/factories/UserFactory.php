<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => fake('id_ID')->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '08' . fake()->numerify('##########'),
            'password' => Hash::make('password'),
            'role' => 'customer',
            'is_active' => fake()->boolean(90),
            'email_verified_at' => fake()->boolean(80) ? now()->subDays(rand(1, 365)) : null,
            'created_at' => now()->subDays(rand(1, 365)),
        ];
    }

    // State: Customer
    public function customer(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'customer',
        ]);
    }

    // State: Agency
    public function agency(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'agency',
        ]);
    }

    // State: Driver
    public function driver(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'driver',
        ]);
    }

    // State: Payment Agent
    public function paymentAgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'payment_agent',
        ]);
    }

    // State: Verified
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }
}