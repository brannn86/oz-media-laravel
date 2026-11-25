<?php

use App\Enums\StationSiteType;
use App\Filament\Pages\StationSettings;
use App\Models\Station;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;

beforeEach(function () {
    $this->station = Station::factory()->create(['slug' => 'oz-bandung', 'name' => 'OZ Bandung']);
});

it('stays off until switched on', function () {
    $this->get('/oz-bandung')->assertInertia(fn (Assert $page) => $page->where('station.ozubathon', null));
    $this->get('/')->assertInertia(fn (Assert $page) => $page->where('ozubathon', null));
});

it('runs 55 hours from 23 December 2026, 00:00 WIB by default', function () {
    $this->station->update(['ozubathon_enabled' => true]);

    $this->get('/oz-bandung')->assertInertia(fn (Assert $page) => $page
        ->where('station.ozubathon.starts_at', '2026-12-23T00:00:00+07:00')
        ->where('station.ozubathon.ends_at', '2026-12-25T07:00:00+07:00')
        ->where('station.ozubathon.hours', 55));
});

it('counts the 55 hours from the start set in the admin', function () {
    $this->station->update(['ozubathon_enabled' => true, 'ozubathon_starts_at' => Carbon::parse('2026-12-23 18:00', 'Asia/Jakarta')]);

    expect($this->station->fresh()->ozubathon())->toMatchArray([
        'starts_at' => '2026-12-23T18:00:00+07:00',
        'ends_at' => '2026-12-26T01:00:00+07:00',
    ]);
});

it('features the station on the landing page while the theme is on', function () {
    $this->station->update(['ozubathon_enabled' => true]);

    $this->get('/')->assertInertia(fn (Assert $page) => $page
        ->where('ozubathon.station.slug', 'oz-bandung')
        ->where('ozubathon.hours', 55)
        ->has('ozubathon.now'));
});

it('never applies to a basic, player-only site', function () {
    $this->station->update(['ozubathon_enabled' => true, 'site_type' => StationSiteType::Basic]);

    expect($this->station->fresh()->ozubathon())->toBeNull();
});

it('is switched on and timed in station settings', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->actingAs(User::factory()->create()->assignRole('dev'));

    Livewire::test(StationSettings::class, ['stationSlug' => 'oz-bandung'])
        ->assertFormFieldHidden('ozubathon_starts_at')
        ->fillForm(['ozubathon_enabled' => true])
        ->assertFormFieldVisible('ozubathon_starts_at')
        ->fillForm(['ozubathon_starts_at' => '2026-12-23 18:00'])
        ->call('save')
        ->assertHasNoFormErrors();

    $station = $this->station->fresh();

    expect($station->ozubathon_enabled)->toBeTrue()
        ->and($station->ozubathon_starts_at->toIso8601String())->toBe('2026-12-23T18:00:00+07:00');
    // The save clears the cached station, so the theme shows straight away.
    $this->get('/oz-bandung')->assertInertia(fn (Assert $page) => $page->whereNot('station.ozubathon', null));
});
