<?php

use App\Enums\StationSiteType;
use App\Models\Station;
use Database\Seeders\OzBaliSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->station = Station::factory()->create([
        'slug' => 'oz-bali',
        'name' => 'OZ Bali',
        'site_type' => StationSiteType::Basic,
    ]);
});

it('gives a basic station one radio page with the sponsor slot', function () {
    $this->get('/oz-bali')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('basic/home')
            ->where('station.slug', 'oz-bali')
            ->where('station.stream.audio_url', fn ($url) => filled($url))
            ->where('sponsor.house_email', config('oz.advertising_email')));
});

it('keeps the privacy policy on a basic station, in its own shell', function () {
    $this->get('/oz-bali/privacy')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('basic/privacy'));
});

it('switches off the other station pages on a basic station', function (string $path) {
    $this->get("/oz-bali/{$path}")->assertNotFound();
})->with(['programs', 'announcers', 'events', 'news', 'rewards', 'about']);

it('seeds OZ Bali as a basic station once', function () {
    $this->station->delete();

    $this->seed(OzBaliSeeder::class);
    $this->seed(OzBaliSeeder::class);

    $bali = Station::where('slug', 'oz-bali')->sole();

    expect($bali->isBasic())->toBeTrue()
        ->and($bali->is_active)->toBeTrue()
        ->and($bali->stream_audio_url)->toBeNull()
        ->and($bali->privacy_policy_content)->toContain('What this site measures');
});
