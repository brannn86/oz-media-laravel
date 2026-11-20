<?php

use App\Enums\AdminRole;
use App\Filament\Pages\MutedListeners;
use App\Filament\Resources\BlockedWords\BlockedWordResource;
use App\Filament\Resources\BlockedWords\Pages\ManageBlockedWords;
use App\Models\BlockedWord;
use App\Models\ChatMessage;
use App\Models\Station;
use App\Models\User;
use App\Support\ProfanityFilter;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->moderator = User::factory()->create()->assignRole(AdminRole::Editorial->value);
});

it('opens both screens for the three admin roles only', function (?AdminRole $role, int $status) {
    $user = User::factory()->create();
    $role && $user->assignRole($role->value);
    $this->actingAs($user);

    $this->get(MutedListeners::getUrl())->assertStatus($status);
    $this->get(BlockedWordResource::getUrl())->assertStatus($status);
})->with([
    'dev' => [AdminRole::Dev, 200],
    'editorial' => [AdminRole::Editorial, 200],
    'social media' => [AdminRole::SocialMedia, 200],
    'listener' => [null, 403],
]);

describe('muted listeners', function () {
    it('lists only people who are muted right now', function () {
        $muted = User::factory()->create(['name' => 'Spammy']);
        $muted->forceFill(['chat_muted_until' => now()->addHour()])->save();
        $expired = User::factory()->create(['name' => 'Was Muted']);
        $expired->forceFill(['chat_muted_until' => now()->subMinute()])->save();

        $this->actingAs($this->moderator);

        Livewire::test(MutedListeners::class)
            ->assertCanSeeTableRecords([$muted])
            ->assertCanNotSeeTableRecords([$expired, $this->moderator]);
    });

    it('unmutes, changes the length and mutes by search', function () {
        $muted = User::factory()->create();
        $muted->forceFill(['chat_muted_until' => now()->addMinutes(10)])->save();
        $quiet = User::factory()->create(['name' => 'Budi Trolling', 'email' => 'budi@example.com']);

        $this->actingAs($this->moderator);

        Livewire::test(MutedListeners::class)
            ->callAction(TestAction::make('changeLength')->table($muted), data: ['minutes' => 1440])
            ->callAction(TestAction::make('muteListener')->table(), data: ['user_id' => $quiet->id, 'minutes' => 60])
            ->assertHasNoActionErrors();

        expect($muted->refresh()->chat_muted_until->diffInHours(now(), true))->toBeGreaterThan(23)
            ->and($quiet->refresh()->isChatMuted())->toBeTrue();

        Livewire::test(MutedListeners::class)
            ->callAction(TestAction::make('unmute')->table($quiet));

        expect($quiet->refresh()->isChatMuted())->toBeFalse();
    });

    it('cannot mute staff through the search', function () {
        $staff = User::factory()->create()->assignRole(AdminRole::SocialMedia->value);
        $this->actingAs($this->moderator);

        Livewire::test(MutedListeners::class)
            ->callAction(TestAction::make('muteListener')->table(), data: ['user_id' => $staff->id, 'minutes' => 60])
            ->assertHasActionErrors(['user_id']);

        expect($staff->refresh()->isChatMuted())->toBeFalse();
    });
});

describe('blocked words', function () {
    it('starts from the default list', function () {
        expect(BlockedWord::where('word', 'anjing')->exists())->toBeTrue();
    });

    it('adds words lower-cased and the chat filter uses them straight away', function () {
        $this->actingAs($this->moderator);

        expect(app(ProfanityFilter::class)->clean('dasar kunyuk'))->toBe('dasar kunyuk');

        Livewire::test(ManageBlockedWords::class)
            ->callAction('create', data: ['word' => '  KUNYUK '])
            ->assertHasNoActionErrors();

        expect(BlockedWord::where('word', 'kunyuk')->exists())->toBeTrue()
            ->and(app(ProfanityFilter::class)->clean('dasar Kunyuk'))->toBe('dasar K*****');
    });

    it('stops masking a word once it is removed', function () {
        $this->actingAs($this->moderator);
        $word = BlockedWord::where('word', 'tolol')->firstOrFail();
        app(ProfanityFilter::class)->clean('warm the cache');

        Livewire::test(ManageBlockedWords::class)
            ->callAction(TestAction::make('delete')->table($word));

        expect(app(ProfanityFilter::class)->clean('tolol'))->toBe('tolol');
    });

    it('rejects duplicates and symbols', function (string $word) {
        $this->actingAs($this->moderator);

        Livewire::test(ManageBlockedWords::class)
            ->callAction('create', data: ['word' => $word])
            ->assertHasActionErrors(['word']);
    })->with([
        'duplicate (any case)' => ['Anjing'],
        'symbols' => ['a$$'],
        'too short' => ['a'],
    ]);

    it('previews how a message would be filtered without posting it', function () {
        $this->actingAs($this->moderator);

        Livewire::test(ManageBlockedWords::class)
            ->callAction('testFilter', data: ['message' => 'dasar anjing'])
            ->assertNotified('Listeners would see:');

        expect(ChatMessage::count())->toBe(0);
    });

    it('masks edited words in live chat messages', function () {
        BlockedWord::create(['word' => 'kunyuk']);
        Station::factory()->create(['slug' => 'oz-bandung']);
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/stations/oz-bandung/chat/messages', ['body' => 'kunyuk!'])
            ->assertCreated()
            ->assertJsonPath('data.body', 'k*****!');
    });
});
