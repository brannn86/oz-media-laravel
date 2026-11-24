<?php

use App\Models\Station;
use Database\Seeders\OzBandungSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

it('shares the station socials as Instagram, TikTok, YouTube, Threads and drops X', function () {
    $this->seed([RolesAndPermissionsSeeder::class, OzBandungSeeder::class]);

    Station::where('slug', 'oz-bandung')->first()->update(['social_links' => [
        'x' => 'https://x.com/ozradiobandung',
        'youtube' => 'https://www.youtube.com/@ozradiobandung',
        'threads' => 'https://www.threads.com/@ozradiobandung',
        'tiktok' => '',
        'instagram' => 'https://www.instagram.com/ozradiobandung',
    ]]);

    $this->get('/oz-bandung')->assertInertia(fn (Assert $page) => $page
        ->where('station.social', fn ($social) => array_keys($social->all()) === ['instagram', 'youtube', 'threads']));
});
