<?php

namespace Database\Factories;

use App\Models\Reward;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reward>
 */
class RewardFactory extends Factory
{
    public function definition(): array
    {
        return [
            'station_id' => null,
            'name' => 'OZ '.ucfirst(fake()->word()),
            'description' => fake()->sentence(),
            'points_cost' => 100,
            'stock' => null,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function limited(int $stock): static
    {
        return $this->state(['stock' => $stock]);
    }
}
