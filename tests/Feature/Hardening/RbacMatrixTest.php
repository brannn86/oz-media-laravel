<?php

use App\Enums\AdminRole;
use App\Filament\Pages\MutedListeners;
use App\Filament\Pages\StationSettings;
use App\Filament\Pages\StudioMode;
use App\Filament\Resources\Announcers\AnnouncerResource;
use App\Filament\Resources\BlockedWords\BlockedWordResource;
use App\Filament\Resources\Events\EventResource;
use App\Filament\Resources\Posts\PostResource;
use App\Filament\Resources\Programs\ProgramResource;
use App\Filament\Resources\ProgramSchedules\ProgramScheduleResource;
use App\Filament\Resources\RewardRedemptions\RewardRedemptionResource;
use App\Filament\Resources\Rewards\RewardResource;
use App\Filament\Resources\SocialHighlights\SocialHighlightResource;
use App\Filament\Resources\Stations\StationResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\ChatMessage;
use App\Models\Post;
use App\Models\Station;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Role;

/*
| Every admin screen against every role. D = dev, E = editorial, S = social media,
| L = listener (no role). true = allowed (200), false = refused (403).
*/

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->station = Station::factory()->create(['slug' => 'oz-bandung']);
});

function actingRole(?AdminRole $role): User
{
    $user = User::factory()->create();
    $role && $user->assignRole($role->value);

    return $user;
}

dataset('screens', function () {
    $matrix = [
        //                            D     E      S      L
        'dashboard' => [fn () => '/admin/dashboard', [true, true, true, false]],
        'programs' => [fn () => ProgramResource::getUrl(), [true, true, false, false]],
        'schedule' => [fn () => ProgramScheduleResource::getUrl(), [true, true, false, false]],
        'announcers' => [fn () => AnnouncerResource::getUrl(), [true, true, false, false]],
        'news' => [fn () => PostResource::getUrl(), [true, true, false, false]],
        'news create' => [fn () => PostResource::getUrl('create'), [true, true, false, false]],
        'events' => [fn () => EventResource::getUrl(), [true, false, true, false]],
        'social highlights' => [fn () => SocialHighlightResource::getUrl(), [true, false, true, false]],
        'stations' => [fn () => StationResource::getUrl(), [true, false, true, false]],
        'station create' => [fn () => StationResource::getUrl('create'), [true, false, false, false]],
        'station settings' => [fn () => StationSettings::getUrl(), [true, false, false, false]],
        'studio mode' => [fn () => StudioMode::getUrl(), [true, true, true, false]],
        'muted listeners' => [fn () => MutedListeners::getUrl(), [true, true, true, false]],
        'blocked words' => [fn () => BlockedWordResource::getUrl(), [true, true, true, false]],
        'rewards' => [fn () => RewardResource::getUrl(), [true, false, true, false]],
        'redemptions' => [fn () => RewardRedemptionResource::getUrl(), [true, false, true, false]],
        'users' => [fn () => UserResource::getUrl(), [true, false, false, false]],
        'roles' => [fn () => '/admin/shield/roles', [true, false, false, false]],
        'pulse' => [fn () => '/pulse', [true, false, false, false]],
    ];

    foreach ($matrix as $screen => [$url, $allowed]) {
        foreach ([AdminRole::Dev, AdminRole::Editorial, AdminRole::SocialMedia, null] as $index => $role) {
            yield "{$screen} / ".($role?->value ?? 'listener') => [$url, $role, $allowed[$index]];
        }
    }
});

it('enforces the role matrix on every admin screen', function (Closure $url, ?AdminRole $role, bool $allowed) {
    $response = $this->actingAs(actingRole($role))->get($url());

    $allowed ? $response->assertOk() : $response->assertForbidden();
})->with('screens');

it('keeps abilities inside screens role-specific', function () {
    $dev = actingRole(AdminRole::Dev);
    $editorial = actingRole(AdminRole::Editorial);
    $social = actingRole(AdminRole::SocialMedia);
    $listener = actingRole(null);
    $post = Post::factory()->for($this->station)->create();

    // Video livestream switch: dev + social media.
    expect($dev->can('toggleVideoLive', $this->station))->toBeTrue()
        ->and($social->can('toggleVideoLive', $this->station))->toBeTrue()
        ->and($editorial->can('toggleVideoLive', $this->station))->toBeFalse()
        // News: dev + editorial.
        ->and($editorial->can('update', $post))->toBeTrue()
        ->and($social->can('update', $post))->toBeFalse()
        // Station settings: dev only.
        ->and($social->can('update', $this->station))->toBeFalse()
        ->and($dev->can('update', $this->station))->toBeTrue()
        // Live chat moderation: every staff role, never listeners.
        ->and($editorial->can('moderate', ChatMessage::class))->toBeTrue()
        ->and($social->can('moderate', ChatMessage::class))->toBeTrue()
        ->and($listener->can('moderate', ChatMessage::class))->toBeFalse()
        // Running song requests and polls on air: every staff role.
        ->and($editorial->can('Host:StudioMode'))->toBeTrue()
        ->and($listener->can('Host:StudioMode'))->toBeFalse()
        // Roles stay fixed, even for dev.
        ->and($dev->can('create', Role::class))->toBeFalse();
});

it('never lets a listener into the panel, even with a valid session', function () {
    $this->actingAs(actingRole(null))->get('/admin/dashboard')->assertForbidden();
});
