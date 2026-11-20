<?php

use App\Models\User;
use Database\Seeders\OzBandungSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([RolesAndPermissionsSeeder::class, OzBandungSeeder::class]);
});

it('returns to the station page after logging in from the popup', function () {
    $user = User::factory()->create();

    $this->from('/oz-bandung/news')
        ->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
            'return' => '/oz-bandung/news',
        ])
        ->assertRedirect('/oz-bandung/news');

    $this->assertAuthenticatedAs($user);
});

it('sends failed popup logins back to the same page with errors', function () {
    $user = User::factory()->create();

    $this->from('/oz-bandung')
        ->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
            'return' => '/oz-bandung',
        ])
        ->assertRedirect('/oz-bandung')
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('ignores return paths that leave the site', function (string $return) {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
        'return' => $return,
    ])->assertRedirect(route('dashboard', absolute: false));
})->with(['https://evil.example', '//evil.example', '/\\evil.example']);

it('returns to the station page after signing up from the popup', function () {
    $this->post(route('register.store'), [
        'name' => 'Sari Listener',
        'email' => 'sari@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'return' => '/oz-bandung/events',
    ])->assertRedirect('/oz-bandung/events');

    $this->assertAuthenticated();
});

it('keeps listeners on the station when they log out', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('logout'), ['return' => '/oz-bandung/programs'])
        ->assertRedirect('/oz-bandung/programs');

    $this->assertGuest();
});

it('logs out to the home page without a safe return path', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('logout'), ['return' => 'https://evil.example'])
        ->assertRedirect('/');
});

it('shows the profile page inside the station site', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/oz-bandung/profile')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('station/profile')
            ->where('station.slug', 'oz-bandung')
            ->where('account.email', $user->email)
            ->where('profile.email_verified', true)
            ->where('profile.two_factor_enabled', false)
            ->has('passwordRules'));
});

it('sends guests to log in before the profile page', function () {
    $this->get('/oz-bandung/profile')->assertRedirect(route('login'));
});

it('saves profile details and stays on the station profile page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from('/oz-bandung/profile')
        ->patch(route('profile.update'), [
            'name' => 'New Name',
            'email' => $user->email,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/oz-bandung/profile');

    expect($user->refresh()->name)->toBe('New Name');
});
