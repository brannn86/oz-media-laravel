<?php

namespace Database\Factories;

use App\Enums\SocialPlatform;
use App\Models\SocialHighlight;
use App\Models\Station;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SocialHighlight>
 */
class SocialHighlightFactory extends Factory
{
    public function definition(): array
    {
        $platforms = SocialPlatform::cases();
        $platform = $platforms[fake()->numberBetween(0, count($platforms) - 1)];

        return [
            'station_id' => Station::factory(),
            'platform' => $platform,
            'post_url' => match ($platform) {
                SocialPlatform::YouTube => 'https://www.youtube.com/watch?v='.Str::random(11),
                SocialPlatform::TikTok => 'https://www.tiktok.com/@ozradiobandung/video/'.fake()->numerify('7###############'),
                SocialPlatform::Instagram => 'https://www.instagram.com/p/'.Str::random(11).'/',
            },
            'title' => rtrim(fake()->sentence(5), '.'),
            'custom_thumbnail' => null,
            'is_featured' => false,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    public function featured(): static
    {
        return $this->state(['is_featured' => true]);
    }
}
