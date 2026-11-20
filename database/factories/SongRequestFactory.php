<?php

namespace Database\Factories;

use App\Enums\SongRequestStatus;
use App\Models\SongRequest;
use App\Models\Station;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SongRequest>
 */
class SongRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'station_id' => Station::factory(),
            'user_id' => User::factory(),
            'song_title' => rtrim(fake()->sentence(3), '.'),
            'artist' => fake()->name(),
            'shoutout_message' => fake()->optional()->sentence(),
            'status' => SongRequestStatus::Pending,
        ];
    }

    public function approved(): static
    {
        return $this->state(['status' => SongRequestStatus::Approved]);
    }
}
