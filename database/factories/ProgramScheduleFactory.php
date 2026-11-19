<?php

namespace Database\Factories;

use App\Enums\DayOfWeek;
use App\Models\Program;
use App\Models\ProgramSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProgramSchedule>
 */
class ProgramScheduleFactory extends Factory
{
    public function definition(): array
    {
        $start = fake()->numberBetween(5, 21);

        return [
            'program_id' => Program::factory(),
            'day_of_week' => fake()->randomElement(DayOfWeek::cases()),
            'start_time' => sprintf('%02d:00', $start),
            'end_time' => sprintf('%02d:00', $start + 2),
        ];
    }

    public function at(DayOfWeek $day, string $start, string $end): static
    {
        return $this->state([
            'day_of_week' => $day,
            'start_time' => $start,
            'end_time' => $end,
        ]);
    }
}
