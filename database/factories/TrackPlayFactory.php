<?php

namespace Database\Factories;

use App\Models\Station;
use App\Models\TrackPlay;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TrackPlay>
 */
class TrackPlayFactory extends Factory
{
    public function definition(): array
    {
        $title = Str::title(fake()->word().' '.fake()->word());
        $artist = fake()->name();

        return [
            'station_id' => Station::factory(),
            'title' => $title,
            'artist' => $artist,
            'raw_title' => "{$title} - {$artist}",
            'listeners' => fake()->numberBetween(0, 50),
            'started_at' => now()->subMinutes(3),
            'ended_at' => null,
        ];
    }

    public function ended(): static
    {
        return $this->state(fn (array $attributes) => [
            'ended_at' => now(),
        ]);
    }
}
