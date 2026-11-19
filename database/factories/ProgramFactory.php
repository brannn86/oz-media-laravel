<?php

namespace Database\Factories;

use App\Models\Program;
use App\Models\Station;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Program>
 */
class ProgramFactory extends Factory
{
    public function definition(): array
    {
        return [
            'station_id' => Station::factory(),
            'title' => fake()->unique()->words(3, true),
            'description' => fake()->paragraph(),
            'cover_image' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
