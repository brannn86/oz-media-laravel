<?php

use App\Enums\AgeRange;
use App\Enums\Gender;
use App\Models\Station;
use App\Models\User;
use Illuminate\Testing\TestResponse;

function signUp(array $details = []): TestResponse
{
    return test()->post(route('register.store'), [
        'name' => 'Sari Listener',
        'email' => 'sari@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        ...$details,
    ]);
}

it('asks for age range, gender and location on the website sign-up', function () {
    signUp()->assertSessionHasErrors(['age_range', 'gender', 'location']);

    expect(User::count())->toBe(0);
});

it('saves the sign-up details, including a place that is not on the list', function () {
    signUp([
        'age_range' => '18_24',
        'gender' => 'prefer_not_to_say',
        'location' => '  Kecamatan Lembang  ',
    ])->assertSessionHasNoErrors();

    $user = User::sole();

    expect($user->age_range)->toBe(AgeRange::From18To24)
        ->and($user->gender)->toBe(Gender::PreferNotToSay)
        ->and($user->location)->toBe('Kecamatan Lembang');
});

it('rejects values that are not one of the options', function () {
    signUp(['age_range' => '99_100', 'gender' => 'robot', 'location' => str_repeat('x', 101)])
        ->assertSessionHasErrors(['age_range', 'gender', 'location']);
});

it('keeps the details optional for the app, which may not send them yet', function () {
    $this->postJson('/api/v1/auth/register', [
        'name' => 'App Listener',
        'email' => 'app@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'device_name' => 'Pixel',
    ])->assertCreated();

    $this->postJson('/api/v1/auth/register', [
        'name' => 'App Listener 2',
        'email' => 'app2@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'device_name' => 'Pixel',
        'age_range' => '35_44',
        'gender' => 'male',
        'location' => 'Kota Denpasar, Bali',
    ])->assertCreated();

    expect(User::where('email', 'app@example.com')->sole()->age_range)->toBeNull()
        ->and(User::where('email', 'app2@example.com')->sole()->location)->toBe('Kota Denpasar, Bali');
});

it('lets listeners change the details on their profile, and other forms leave them alone', function () {
    $user = User::factory()->create(['age_range' => '25_34', 'gender' => 'female', 'location' => 'Kota Bandung, Jawa Barat']);

    $this->actingAs($user)->patch(route('profile.update'), [
        'name' => $user->name,
        'email' => $user->email,
        'age_range' => '35_44',
        'gender' => 'female',
        'location' => 'Kota Cimahi, Jawa Barat',
    ])->assertSessionHasNoErrors();

    expect($user->refresh()->age_range)->toBe(AgeRange::From35To44)
        ->and($user->location)->toBe('Kota Cimahi, Jawa Barat');

    // The settings page only sends name and email.
    $this->actingAs($user)->patch(route('profile.update'), ['name' => 'New Name', 'email' => $user->email])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->location)->toBe('Kota Cimahi, Jawa Barat');
});

it('shows the details on the station profile page', function () {
    Station::factory()->create(['slug' => 'oz-bandung']);
    $user = User::factory()->create(['age_range' => '25_34', 'gender' => 'male', 'location' => 'Kota Bandung, Jawa Barat']);

    $this->actingAs($user)->get('/oz-bandung/profile')->assertInertia(fn ($page) => $page
        ->where('profile.age_range', '25_34')
        ->where('profile.gender', 'male')
        ->where('profile.location', 'Kota Bandung, Jawa Barat'));
});
