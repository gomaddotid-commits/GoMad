<?php

namespace Database\Factories;

use App\Models\PaymentAgent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class PaymentAgentFactory extends Factory
{
    protected $model = PaymentAgent::class;

    public function definition(): array
    {
        $cities = ['Sumenep', 'Pamekasan', 'Bangkalan', 'Surabaya', 'Probolinggo', 'Jember', 'Malang'];
        $city = fake()->randomElement($cities);

        return [
            'agent_name' => fake('id_ID')->company() . ' Payment',
            'owner_name' => fake('id_ID')->name(),
            'owner_phone' => '08' . fake()->numerify('##########'),
            'guard_name' => fake()->boolean(70) ? fake('id_ID')->name() : null,
            'guard_phone' => fake()->boolean(70) ? '08' . fake()->numerify('##########') : null,
            'address' => fake('id_ID')->address(),
            'kecamatan' => $city,
            'maps_link' => 'https://maps.google.com/?q=' . fake()->latitude(-8, -7) . ',' . fake()->longitude(112, 114),
            'latitude' => fake()->latitude(-8, -7),
            'longitude' => fake()->longitude(112, 114),
            'pin' => Hash::make('123456'),
            'is_active' => true,
            'is_verified' => false,
            'commission_rate' => 2.00,
            'total_transactions' => 0,
            'total_commission' => 0,
            'balance_to_settle' => 0,
        ];
    }

    // State: Verified
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
            'total_transactions' => rand(5, 50),
            'total_commission' => rand(30000, 300000),
            'balance_to_settle' => rand(50000, 1500000),
        ]);
    }
}