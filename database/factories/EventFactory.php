<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Station;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    public function definition(): array
    {
        $start = Carbon::instance(fake()->dateTimeBetween('+1 week', '+3 months'))->setTime(19, 0);

        return [
            'station_id' => Station::factory(),
            'title' => rtrim(fake()->unique()->sentence(3), '.'),
            'organizer_name' => fake()->company(),
            'is_oz_event' => false,
            'location' => fake()->streetAddress().', Bandung',
            'start_date' => $start,
            'end_date' => $start->copy()->addHours(4),
            'description' => fake()->paragraphs(2, true),
            'poster_image' => null,
            'ticket_url' => fake()->optional()->url(),
        ];
    }

    public function ozEvent(): static
    {
        return $this->state([
            'is_oz_event' => true,
            'organizer_name' => 'OZ Radio Bandung',
        ]);
    }

    public function past(): static
    {
        return $this->state(function () {
            $start = Carbon::instance(fake()->dateTimeBetween('-3 months', '-1 week'))->setTime(19, 0);

            return ['start_date' => $start, 'end_date' => $start->copy()->addHours(4)];
        });
    }
}
