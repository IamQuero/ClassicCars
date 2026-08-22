<?php

namespace Database\Factories;

use App\Models\Car;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ListingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'car_id' => Car::factory(),
            'seller_id' => User::factory(),
            'price' => fake()->numberBetween(8000, 120000),
            'status' => 'published',
            'published_at' => fake()->dateTimeBetween('-6 months', 'now'),
            'expires_at' => fake()->dateTimeBetween('+1 month', '+6 months'),
        ];
    }

    public function sold(): static
    {
        return $this->state(fn () => [
            'status' => 'sold',
            'expires_at' => null,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'status' => 'draft',
            'published_at' => null,
            'expires_at' => null,
        ]);
    }
}