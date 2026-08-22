<?php

namespace Database\Factories;

use App\Models\Listing;
use App\Models\User;
use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sender_id' => User::factory(),
            'receiver_id' => User::factory(),
            'listing_id' => Listing::factory(),
            'message' => fake()->sentence(),
            'read_at' => fake()->optional(0.4)->dateTimeThisMonth(),
        ];
    }
}
