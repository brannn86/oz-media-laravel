<?php

use App\Models\Station;
use App\Models\TrackPlay;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->station = Station::factory()->create(['slug' => 'oz-bandung']);

    $this->old = TrackPlay::factory()->for($this->station)->create([
        'title' => 'Komang', 'artist' => 'Raim Laode',
        'started_at' => now()->subMinutes(8), 'ended_at' => now()->subMinutes(4),
    ]);
    $this->current = TrackPlay::factory()->for($this->station)->create([
        'title' => 'Hatchu!!', 'artist' => 'Salma Salsabil',
        'started_at' => now()->subMinutes(4),
    ]);
    TrackPlay::factory()->create(['title' => 'Elsewhere']);
});

it('shares the song on air and recently played with station pages', function () {
    $this->get('/oz-bandung')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('now_playing.track.title', 'Hatchu!!')
            ->where('now_playing.track.artist', 'Salma Salsabil')
            ->has('now_playing.recent', 1)
            ->where('now_playing.recent.0.title', 'Komang'));
});

it('adds the song on air to the now-playing API', function () {
    $this->getJson('/api/v1/stations/oz-bandung/now-playing')
        ->assertOk()
        ->assertJsonPath('now_playing.track.title', 'Hatchu!!')
        ->assertJsonPath('now_playing.recent.0.title', 'Komang')
        ->assertJsonCount(1, 'now_playing.recent')
        ->assertJsonStructure(['data' => ['at', 'current', 'next']]);
});

it('reports no song between tracks', function () {
    $this->current->update(['ended_at' => now()]);

    $this->getJson('/api/v1/stations/oz-bandung/now-playing')
        ->assertJsonPath('now_playing.track', null)
        ->assertJsonCount(2, 'now_playing.recent');
});
