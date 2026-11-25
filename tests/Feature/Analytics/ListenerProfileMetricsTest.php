<?php

use App\Actions\Analytics\ListenerProfileMetrics;
use App\Enums\AdminRole;
use App\Filament\Analytics\Widgets\ListenerLocationsTable;
use App\Filament\Analytics\Widgets\ListenerProfileChart;
use App\Filament\Pages\Analytics\AudienceAnalytics;
use App\Models\Station;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;

beforeEach(function () {
    $this->station = Station::factory()->create(['slug' => 'oz-bandung']);
    $this->travelTo(Carbon::parse('2026-11-26 10:00:05', 'Asia/Jakarta'));
});

function listener(string $age, string $gender, string $location): User
{
    return User::factory()->create(['age_range' => $age, 'gender' => $gender, 'location' => $location]);
}

function listenedAs(User $user, int $minutes = 30): void
{
    DB::table('audience_sessions')->insert([
        'station_id' => test()->station->id,
        'mode' => 'audio',
        'visitor' => sha1("user:{$user->id}"),
        'started_at' => now()->subHour(),
        'last_seen_at' => now()->subHour()->addMinutes($minutes),
        'minutes' => $minutes,
        'source' => 'Direct',
        'age_range' => $user->age_range?->value,
        'gender' => $user->gender?->value,
        'location' => $user->location,
    ]);
}

it('labels a signed-in listener\'s session with their details, not their account', function () {
    $user = listener('25_34', 'female', 'Kota Bandung, Jawa Barat');
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/stations/oz-bandung/audience', ['mode' => 'audio'])->assertNoContent();

    $session = DB::table('audience_sessions')->sole();

    expect($session->age_range)->toBe('25_34')
        ->and($session->gender)->toBe('female')
        ->and($session->location)->toBe('Kota Bandung, Jawa Barat')
        ->and((array) $session)->not->toHaveKey('user_id');
});

it('leaves guests\' sessions without details', function () {
    $this->withHeader('X-Listener-Id', 'guest-phone')
        ->postJson('/api/v1/stations/oz-bandung/audience', ['mode' => 'audio'])
        ->assertNoContent();

    expect(DB::table('audience_sessions')->sole()->age_range)->toBeNull();
});

it('counts accounts and who of them listened, per age range and gender', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $a = listener('18_24', 'female', 'Kota Bandung, Jawa Barat');
    $b = listener('18_24', 'male', 'Kota Bandung, Jawa Barat');
    listener('25_34', 'female', 'Kota Cimahi, Jawa Barat'); // Signed up, didn't listen.
    listener('18_24', 'male', 'Kota Bandung, Jawa Barat')->assignRole(AdminRole::Editorial->value); // Staff: left out.

    listenedAs($a, 60);
    listenedAs($a, 30);
    listenedAs($b, 30);

    $ages = collect(app(ListenerProfileMetrics::class)->breakdown('age_range', 30))->keyBy('label');
    $genders = collect(app(ListenerProfileMetrics::class)->breakdown('gender', 30))->keyBy('label');

    expect($ages['18–24'])->toBe(['label' => '18–24', 'accounts' => 2, 'listeners' => 2, 'hours' => 2.0])
        ->and($ages['25–34'])->toMatchArray(['accounts' => 1, 'listeners' => 0])
        ->and($ages)->toHaveCount(7)
        ->and($genders['Female'])->toMatchArray(['accounts' => 2, 'listeners' => 1, 'hours' => 1.5]);
});

it('counts small areas together so no one is singled out', function () {
    foreach (range(1, 5) as $i) {
        listenedAs(listener('18_24', 'female', 'Kota Bandung, Jawa Barat'));
    }
    listenedAs(listener('35_44', 'male', 'Kabupaten Lebak, Banten'));
    listener('45_54', 'male', 'Kecamatan Lembang');

    $areas = app(ListenerProfileMetrics::class)->locations(30);

    expect($areas)->toBe([
        ['label' => 'Kota Bandung, Jawa Barat', 'accounts' => 5, 'listeners' => 5, 'hours' => 2.5],
        ['label' => 'Other areas', 'accounts' => 2, 'listeners' => 1, 'hours' => 0.5],
    ]);
});

it('says how much of the listening the breakdowns cover', function () {
    listenedAs(listener('18_24', 'female', 'Kota Bandung, Jawa Barat'));
    DB::table('audience_sessions')->insert([
        'station_id' => $this->station->id, 'mode' => 'audio', 'visitor' => sha1('guest'),
        'started_at' => now()->subHour(), 'last_seen_at' => now()->subHour(), 'minutes' => 1, 'source' => 'Direct',
    ]);

    expect(app(ListenerProfileMetrics::class)->coverage(30))->toBe(['sessions' => 2, 'profiled' => 1]);
});

it('shows the breakdowns on the Audience page', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    listenedAs(listener('18_24', 'female', 'Kota Bandung, Jawa Barat'));
    $this->actingAs(User::factory()->create()->assignRole(AdminRole::Dev->value));

    $this->get(AudienceAnalytics::getUrl())->assertOk();

    Livewire::test(ListenerProfileChart::class, ['dimension' => 'age_range'])
        ->assertSee('Age')
        ->assertSee('100% of the 30 days');
    Livewire::test(ListenerLocationsTable::class)
        ->assertSee('Where listeners live, last 30 days')
        ->assertSee('Other areas');
});
