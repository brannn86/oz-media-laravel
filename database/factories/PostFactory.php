<?php

namespace Database\Factories;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\Station;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    public function definition(): array
    {
        $title = rtrim(fake()->unique()->sentence(6), '.');

        return [
            'station_id' => Station::factory(),
            'author_id' => User::factory(),
            'title' => $title,
            'excerpt' => fake()->sentence(20),
            'body' => collect(range(1, 5))->map(fn (): string => '<p>'.fake()->paragraph().'</p>')->implode("\n"),
            'featured_image' => null,
            'status' => PostStatus::Draft,
            'published_at' => null,
            'meta_title' => null,
            'meta_description' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => PostStatus::Published,
            'published_at' => fake()->dateTimeBetween('-2 months', 'now'),
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn () => [
            'status' => PostStatus::Published,
            'published_at' => fake()->dateTimeBetween('+1 day', '+2 weeks'),
        ]);
    }
}
