<?php

use App\Models\Event;
use App\Models\Station;
use Database\Seeders\OzBandungSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([RolesAndPermissionsSeeder::class, OzBandungSeeder::class]);
    $this->station = Station::where('slug', 'oz-bandung')->first();
});

it('gives every event its own page with what else is coming up', function () {
    $event = Event::factory()->for($this->station)->create([
        'title' => 'OZ Night Market',
        'start_date' => now()->addWeek(),
        'end_date' => null,
    ]);

    $this->get("/oz-bandung/events/{$event->slug}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('station/events/show')
            ->where('event.id', $event->id)
            ->where('event.title', 'OZ Night Market')
            ->where('meta.title', 'OZ Night Market | OZ Bandung')
            ->where('upcoming', fn ($upcoming) => count($upcoming) <= 3
                && collect($upcoming)->doesntContain('id', $event->id)));
});

it('keeps past events reachable', function () {
    $event = Event::factory()->for($this->station)->create([
        'start_date' => now()->subMonth(),
        'end_date' => now()->subMonth()->addHours(3),
    ]);

    $this->get("/oz-bandung/events/{$event->slug}")->assertOk();
});

it('404s events that belong to another station or do not exist', function () {
    $elsewhere = Event::factory()->create();

    $this->get("/oz-bandung/events/{$elsewhere->slug}")->assertNotFound();
    $this->get('/oz-bandung/events/no-such-event')->assertNotFound();
});
