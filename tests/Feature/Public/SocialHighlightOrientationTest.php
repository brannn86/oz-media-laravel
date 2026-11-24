<?php

use App\Enums\SocialPlatform;
use App\Enums\VideoOrientation;
use App\Models\SocialHighlight;
use App\Models\Station;
use Database\Seeders\OzBandungSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

it('detects which way a social video is shot from its platform and link', function (SocialPlatform $platform, string $url, VideoOrientation $expected) {
    expect(SocialHighlight::detectOrientation($platform, $url))->toBe($expected);
})->with([
    'YouTube video' => [SocialPlatform::YouTube, 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', VideoOrientation::Landscape],
    'youtu.be link' => [SocialPlatform::YouTube, 'https://youtu.be/dQw4w9WgXcQ', VideoOrientation::Landscape],
    'YouTube Short' => [SocialPlatform::YouTube, 'https://www.youtube.com/shorts/dQw4w9WgXcQ', VideoOrientation::Portrait],
    'TikTok' => [SocialPlatform::TikTok, 'https://www.tiktok.com/@ozradiobandung/video/7300000000000000000', VideoOrientation::Portrait],
    'Instagram' => [SocialPlatform::Instagram, 'https://www.instagram.com/reel/Cabc123/', VideoOrientation::Portrait],
]);

it('lets the admin override the detected format', function () {
    $this->seed([RolesAndPermissionsSeeder::class, OzBandungSeeder::class]);
    $station = Station::where('slug', 'oz-bandung')->first();
    $station->socialHighlights()->delete();

    SocialHighlight::factory()->for($station)->featured()->create([
        'platform' => SocialPlatform::YouTube,
        'post_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        'orientation' => VideoOrientation::Portrait,
        'sort_order' => 1,
    ]);
    SocialHighlight::factory()->for($station)->featured()->create([
        'platform' => SocialPlatform::YouTube,
        'post_url' => 'https://www.youtube.com/watch?v=aaaaaaaaaaa',
        'orientation' => null,
        'sort_order' => 2,
    ]);

    $this->get('/oz-bandung')->assertInertia(fn (Assert $page) => $page
        ->where('highlights.0.orientation', 'portrait')
        ->where('highlights.1.orientation', 'landscape'));
});
