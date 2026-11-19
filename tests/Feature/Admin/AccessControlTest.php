<?php

use App\Enums\AdminRole;
use App\Filament\Pages\StationSettings;
use App\Filament\Resources\Announcers\AnnouncerResource;
use App\Filament\Resources\Events\EventResource;
use App\Filament\Resources\Posts\PostResource;
use App\Filament\Resources\Programs\ProgramResource;
use App\Filament\Resources\ProgramSchedules\ProgramScheduleResource;
use App\Filament\Resources\SocialHighlights\SocialHighlightResource;
use App\Filament\Resources\Stations\StationResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\Station;
use App\Models\User;
use BezhanSalleh\FilamentShield\Resources\Roles\Pages\EditRole;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->station = Station::factory()->create();
});

function userWithRole(?AdminRole $role): User
{
    $user = User::factory()->create();

    if ($role) {
        $user->assignRole($role->value);
    }

    return $user;
}

it('seeds exactly the three admin roles and promotes the admin account to dev', function () {
    expect(Role::pluck('name')->sort()->values()->all())->toBe(['dev', 'editorial', 'social_media'])
        ->and(User::where('email', 'admin@ozmedianet.com')->first()->hasRole('dev'))->toBeTrue();
});

it('keeps users without an admin role out of the panel', function () {
    $this->actingAs(userWithRole(null))
        ->get(ProgramResource::getUrl('index'))
        ->assertForbidden();
});

it('enforces the role matrix on every admin page', function (string $page, AdminRole $role, bool $allowed) {
    $urls = [
        'programs' => ProgramResource::getUrl('index'),
        'schedule' => ProgramScheduleResource::getUrl('index'),
        'announcers' => AnnouncerResource::getUrl('index'),
        'news' => PostResource::getUrl('index'),
        'events' => EventResource::getUrl('index'),
        'social highlights' => SocialHighlightResource::getUrl('index'),
        'stations' => StationResource::getUrl('index'),
        'station settings' => StationSettings::getUrl(),
        'users' => UserResource::getUrl('index'),
        'roles' => '/admin/shield/roles',
    ];

    $response = $this->actingAs(userWithRole($role))->get($urls[$page]);

    $allowed ? $response->assertOk() : $response->assertForbidden();
})->with(function () {
    $matrix = [
        //                     dev   editorial social_media
        'programs' => [true, true, false],
        'schedule' => [true, true, false],
        'announcers' => [true, true, false],
        'news' => [true, true, false],
        'events' => [true, false, true],
        'social highlights' => [true, false, true],
        'stations' => [true, false, true],
        'station settings' => [true, false, false],
        'users' => [true, false, false],
        'roles' => [true, false, false],
    ];

    foreach ($matrix as $page => [$dev, $editorial, $social]) {
        yield "{$page} / dev" => [$page, AdminRole::Dev, $dev];
        yield "{$page} / editorial" => [$page, AdminRole::Editorial, $editorial];
        yield "{$page} / social_media" => [$page, AdminRole::SocialMedia, $social];
    }
});

it('only lets dev and social media toggle the video livestream', function () {
    expect(userWithRole(AdminRole::Dev)->can('toggleVideoLive', $this->station))->toBeTrue()
        ->and(userWithRole(AdminRole::SocialMedia)->can('toggleVideoLive', $this->station))->toBeTrue()
        ->and(userWithRole(AdminRole::Editorial)->can('toggleVideoLive', $this->station))->toBeFalse();
});

it('does not let social media edit station settings', function () {
    expect(userWithRole(AdminRole::SocialMedia)->can('update', $this->station))->toBeFalse();
});

it('refuses to rename a built-in role', function () {
    Role::findByName('dev')->update(['name' => 'Developer']);
})->throws(ValidationException::class);

it('shows the rename error on the Edit Role form and keeps the role intact', function () {
    $this->actingAs(userWithRole(AdminRole::Dev));
    $role = Role::findByName('dev');

    Livewire::test(EditRole::class, ['record' => $role->getKey()])
        ->fillForm(['name' => 'Developer'])
        ->call('save')
        ->assertHasFormErrors(['name']);

    expect($role->refresh()->name)->toBe('dev');
});

it('never allows creating or deleting roles, even for dev', function () {
    $dev = userWithRole(AdminRole::Dev);

    expect($dev->can('create', Role::class))->toBeFalse()
        ->and($dev->can('delete', Role::findByName('editorial')))->toBeFalse()
        ->and($dev->can('update', Role::findByName('editorial')))->toBeTrue();
});
