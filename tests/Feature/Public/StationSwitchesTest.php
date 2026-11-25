<?php

use App\Models\Station;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->station = Station::factory()->create(['slug' => 'oz-bandung']);
});

it('hides the OZ Poin page when it is switched off, and points still accrue', function () {
    $this->get('/oz-bandung/rewards')->assertOk();
    $this->get('/oz-bandung')->assertInertia(fn (Assert $page) => $page->where('station.rewards_page', true));

    $this->station->update(['rewards_page_enabled' => false]);

    $this->get('/oz-bandung/rewards')->assertNotFound();
    $this->get('/oz-bandung')->assertInertia(fn (Assert $page) => $page->where('station.rewards_page', false));

    // Earning is untouched: the reward API keeps working.
    Sanctum::actingAs(User::factory()->create());
    $this->getJson('/api/v1/me/points')->assertSuccessful();
});

it('removes the ad space when it is switched off', function () {
    $this->get('/oz-bandung')->assertInertia(fn (Assert $page) => $page->has('sponsor.house_email'));

    $this->station->update(['sponsor_enabled' => false]);

    $this->get('/oz-bandung')->assertInertia(fn (Assert $page) => $page->where('sponsor', null));
});
