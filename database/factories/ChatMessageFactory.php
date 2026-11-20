<?php

namespace Database\Factories;

use App\Models\ChatMessage;
use App\Models\Station;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChatMessage>
 */
class ChatMessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'station_id' => Station::factory(),
            'user_id' => User::factory(),
            'body' => fake()->sentence(),
            'is_pinned' => false,
        ];
    }

    public function pinned(): static
    {
        return $this->state(['is_pinned' => true]);
    }
}
