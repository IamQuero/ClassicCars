<?php

namespace Database\Factories;

use App\Models\Listing;
use Illuminate\Database\Eloquent\Factories\Factory;

class PhotoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'listing_id' => Listing::factory(),
            'path' => 'listings/'.fake()->uuid().'.jpg',
            'type' => fake()->randomElement(['exterior', 'interior', 'engine', 'documents']),
            'order' => 0,
        ];
    }
}