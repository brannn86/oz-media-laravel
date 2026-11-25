<?php

use App\Enums\AdminRole;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\StudioMode;
use App\Models\Station;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Station::factory()->create(['slug' => 'oz-bandung']);
    config(['broadcasting.default' => 'null']);
});

it('sends the bare admin address to the dashboard', function () {
    $this->actingAs(User::factory()->create()->assignRole(AdminRole::Dev->value))
        ->get('/admin')
        ->assertRedirect(Dashboard::getUrl());

    expect(Dashboard::getUrl())->toEndWith('/admin/dashboard');
});

it('sends guests on the admin address to the login', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('serves Studio Mode on its own panel', function () {
    $this->actingAs(User::factory()->create()->assignRole(AdminRole::Editorial->value));

    $this->get('/studio')->assertRedirect(StudioMode::getUrl(panel: 'studio'));
    $this->get(StudioMode::getUrl(panel: 'studio'))->assertOk()->assertSee('OZ Studio');
});

it('keeps listeners out of the studio', function () {
    $this->actingAs(User::factory()->create())
        ->get(StudioMode::getUrl(panel: 'studio'))
        ->assertForbidden();
});

it('links the studio from the admin sidebar', function () {
    $this->actingAs(User::factory()->create()->assignRole(AdminRole::Dev->value))
        ->get(Dashboard::getUrl())
        ->assertOk()
        ->assertSee(StudioMode::getUrl(panel: 'studio'), false);
});
