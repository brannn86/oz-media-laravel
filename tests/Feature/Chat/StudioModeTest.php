<?php

use App\Enums\AdminRole;
use App\Events\Chat\ChatMessageDeleted;
use App\Events\Chat\ChatMessagePinned;
use App\Events\Chat\ChatSettingsUpdated;
use App\Filament\Pages\StudioMode;
use App\Models\ChatMessage;
use App\Models\Station;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Event::fake([ChatMessageDeleted::class, ChatMessagePinned::class, ChatSettingsUpdated::class]);
    $this->station = Station::factory()->create(['slug' => 'oz-bandung']);
    // Keep the listener count from calling a real Reverb server.
    config(['broadcasting.default' => 'null']);
});

it('opens for all three admin roles and nobody else', function (?AdminRole $role, int $status) {
    $user = User::factory()->create();
    $role && $user->assignRole($role->value);

    $this->actingAs($user)->get(StudioMode::getUrl())->assertStatus($status);
})->with([
    'dev' => [AdminRole::Dev, 200],
    'editorial' => [AdminRole::Editorial, 200],
    'social media' => [AdminRole::SocialMedia, 200],
    'listener' => [null, 403],
]);

it('listens to the station chat channel over Reverb', function () {
    $this->actingAs(User::factory()->create()->assignRole(AdminRole::Editorial->value));

    $listeners = Livewire::test(StudioMode::class)->instance()->getListeners();

    expect(array_keys($listeners))->toContain('echo:station.oz-bandung.chat,.chat.message.sent');
});

it('pins, deletes and mutes with one click', function () {
    $this->actingAs(User::factory()->create()->assignRole(AdminRole::SocialMedia->value));
    $listener = User::factory()->create();
    $message = ChatMessage::factory()->for($this->station)->for($listener)->create(['body' => 'Request: lagu galau dong']);

    Livewire::test(StudioMode::class)
        ->assertSee('Request: lagu galau dong')
        ->callAction(TestAction::make('pin')->arguments(['message' => $message->id]))
        ->call('setMuteMinutes', 60)
        ->callAction(TestAction::make('mute')->arguments(['message' => $message->id]))
        ->callAction(TestAction::make('deleteMessage')->arguments(['message' => $message->id]))
        ->assertDontSee('Request: lagu galau dong');

    expect($message->refresh()->trashed())->toBeTrue()
        ->and($listener->refresh()->chat_muted_until->diffInMinutes(now(), true))->toBeGreaterThan(58);
    Event::assertDispatched(ChatMessagePinned::class);
    Event::assertDispatched(ChatMessageDeleted::class);
});

it('offers mute on listener messages but not on staff messages', function () {
    $staff = User::factory()->create()->assignRole(AdminRole::Editorial->value);
    $this->actingAs($staff);
    ChatMessage::factory()->for($this->station)->for($staff)->create();

    $html = Livewire::test(StudioMode::class)->html();
    expect($html)->not->toContain("mountAction('mute'");

    ChatMessage::factory()->for($this->station)->create();

    expect(Livewire::test(StudioMode::class)->html())->toContain("mountAction('mute'");
});

it('cannot touch another station\'s messages', function () {
    $this->actingAs(User::factory()->create()->assignRole(AdminRole::Dev->value));
    $elsewhere = ChatMessage::factory()->create();

    Livewire::test(StudioMode::class)
        ->callAction(TestAction::make('deleteMessage')->arguments(['message' => $elsewhere->id]));

    expect($elsewhere->refresh()->trashed())->toBeFalse();
});

it('switches slow mode to one of the allowed values', function () {
    $this->actingAs(User::factory()->create()->assignRole(AdminRole::Editorial->value));

    Livewire::test(StudioMode::class)
        ->call('setSlowMode', 30)
        ->call('setSlowMode', 999);

    expect($this->station->refresh()->chat_slow_mode_seconds)->toBe(30);
    Event::assertDispatchedTimes(ChatSettingsUpdated::class, 1);
});

it('shows what listeners can tune in to, without a mode switch', function () {
    $this->actingAs(User::factory()->create()->assignRole(AdminRole::SocialMedia->value));

    $this->station->update(['stream_video_url' => 'https://www.youtube.com/watch?v=abcdefghijk']);
    Livewire::test(StudioMode::class)->assertSee('Audio and Video')->assertActionDoesNotExist('toggleStreamMode');

    $this->station->update(['stream_video_url' => null]);
    Livewire::test(StudioMode::class)->assertDontSee('Audio and Video');
});
