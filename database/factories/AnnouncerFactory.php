<?php

namespace Database\Factories;

use App\Models\Announcer;
use App\Models\Station;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcer>
 */
class AnnouncerFactory extends Factory
{
    public function definition(): array
    {
        $handle = fake()->unique()->userName();

        return [
            'station_id' => Station::factory(),
            'name' => fake()->name(),
            'stage_name' => fake()->optional()->firstName(),
            'bio' => fake()->paragraph(),
            'avatar' => null,
            'social_links' => [
                'instagram' => "https://instagram.com/{$handle}",
                'tiktok' => null,
                'x' => null,
            ],
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
