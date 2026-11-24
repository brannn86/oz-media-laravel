<?php

namespace Database\Factories;

use App\Models\Station;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Station>
 */
class StationFactory extends Factory
{
    public function definition(): array
    {
        $name = 'OZ '.fake()->unique()->city();

        return [
            'slug' => Str::slug($name),
            'name' => $name,
            'tagline' => rtrim(fake()->sentence(4), '.'),
            'stream_audio_url' => 'https://stream.example.com/'.Str::slug($name).'.mp3',
            'stream_video_url' => null,
            'is_video_live' => false,
            'contact_info' => [
                'email' => fake()->companyEmail(),
                'phone' => fake()->phoneNumber(),
                'address' => fake()->address(),
                'whatsapp' => fake()->e164PhoneNumber(),
            ],
            'social_links' => [
                'instagram' => null,
                'tiktok' => null,
                'youtube' => null,
                'threads' => null,
            ],
            'about_content' => fake()->paragraphs(3, true),
            'privacy_policy_content' => fake()->paragraphs(4, true),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function videoLive(): static
    {
        return $this->state([
            'stream_video_url' => 'https://www.youtube.com/embed/live_stream?channel=example',
            'is_video_live' => true,
        ]);
    }
}
