<?php

use App\Models\Station;
use Database\Seeders\OzBandungSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([RolesAndPermissionsSeeder::class, OzBandungSeeder::class]);
    $this->station = Station::where('slug', 'oz-bandung')->first();
});

it('shares the station socials as Instagram, TikTok, YouTube, Threads, X', function () {
    $this->station->update(['social_links' => [
        'x' => 'https://x.com/ozradiobandung',
        'youtube' => 'https://www.youtube.com/@ozradiobandung',
        'threads' => 'https://www.threads.com/@ozradiobandung',
        'facebook' => 'https://www.facebook.com/ozradiobandung',
        'instagram' => 'https://www.instagram.com/ozradiobandung',
    ]]);

    $this->get('/oz-bandung')->assertInertia(fn (Assert $page) => $page
        ->where('station.social', fn ($social) => array_keys($social->all()) === ['instagram', 'youtube', 'threads', 'x']));
});

it('hides networks left empty, X included', function () {
    $this->station->update(['social_links' => [
        'instagram' => 'https://www.instagram.com/ozradiobandung',
        'tiktok' => '',
        'x' => null,
    ]]);

    $this->get('/oz-bandung')->assertInertia(fn (Assert $page) => $page
        ->where('station.social', fn ($social) => array_keys($social->all()) === ['instagram']));
});

it('shares the map link for the address, but only web links', function () {
    $this->station->update(['contact_info' => [
        ...$this->station->contact_info,
        'map_url' => 'https://maps.app.goo.gl/oz-bandung',
    ]]);

    $this->get('/oz-bandung')->assertInertia(fn (Assert $page) => $page
        ->where('station.contact.map_url', 'https://maps.app.goo.gl/oz-bandung'));

    $this->station->update(['contact_info' => [
        ...$this->station->contact_info,
        'map_url' => 'javascript:alert(1)',
    ]]);

    $this->get('/oz-bandung')->assertInertia(fn (Assert $page) => $page
        ->missing('station.contact.map_url'));
});
