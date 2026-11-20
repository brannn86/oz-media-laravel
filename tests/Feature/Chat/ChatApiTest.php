<?php

use App\Actions\Chat\ModerateChatAction;
use App\Enums\AdminRole;
use App\Events\Chat\ChatMessageDeleted;
use App\Events\Chat\ChatMessagePinned;
use App\Events\Chat\ChatMessageSent;
use App\Events\Chat\ChatSettingsUpdated;
use App\Models\ChatMessage;
use App\Models\Station;
use App\Models\User;
use App\Support\ProfanityFilter;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Event::fake([ChatMessageSent::class, ChatMessageDeleted::class, ChatMessagePinned::class, ChatSettingsUpdated::class]);
    $this->station = Station::factory()->create(['slug' => 'oz-bandung']);
    $this->listener = User::factory()->create(['name' => 'Rina']);
    $this->url = '/api/v1/stations/oz-bandung/chat';
});

describe('reading', function () {
    it('lets anyone read recent history, the pinned message and the settings', function () {
        ChatMessage::factory()->for($this->station)->count(3)->create();
        $pinned = ChatMessage::factory()->for($this->station)->pinned()->create();
        ChatMessage::factory()->for($this->station)->create()->delete();

        $this->getJson($this->url)
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonPath('pinned.id', $pinned->id)
            ->assertJsonPath('settings.channel', 'station.oz-bandung.chat')
            ->assertJsonPath('settings.cooldown_seconds', 3)
            ->assertJsonPath('me', null)
            ->assertJsonMissingPath('data.0.user.email');
    });

    it('tells a signed-in listener who they are', function () {
        Sanctum::actingAs($this->listener);

        $this->getJson($this->url)->assertJsonPath('me.name', 'Rina');
    });

    it('404s inactive stations', function () {
        $this->station->update(['is_active' => false]);

        $this->getJson($this->url)->assertNotFound();
    });
});

describe('sending', function () {
    it('requires an account', function () {
        $this->postJson("{$this->url}/messages", ['body' => 'Halo!'])->assertUnauthorized();
    });

    it('saves, masks profanity and broadcasts the message', function () {
        Sanctum::actingAs($this->listener);

        $this->postJson("{$this->url}/messages", ['body' => '  Lagu ini  anjiiing   keren!  '])
            ->assertCreated()
            ->assertJsonPath('data.body', 'Lagu ini a******* keren!')
            ->assertJsonPath('data.user.name', 'Rina')
            ->assertJsonPath('cooldown_seconds', 3);

        Event::assertDispatched(ChatMessageSent::class, fn (ChatMessageSent $event) => $event->broadcastOn()[0]->name === 'station.oz-bandung.chat'
            && $event->broadcastWith()['message']['body'] === 'Lagu ini a******* keren!');
    });

    it('allows one message every 3 seconds per listener', function () {
        Sanctum::actingAs($this->listener);

        $this->postJson("{$this->url}/messages", ['body' => 'one'])->assertCreated();
        $this->postJson("{$this->url}/messages", ['body' => 'two'])
            ->assertTooManyRequests()
            ->assertHeader('Retry-After');

        $this->travel(4)->seconds();

        $this->postJson("{$this->url}/messages", ['body' => 'three'])->assertCreated();
    });

    it('applies slow mode to listeners but only the 3 second floor to staff', function () {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->station->update(['chat_slow_mode_seconds' => 30]);
        $staff = User::factory()->create()->assignRole(AdminRole::Editorial->value);

        Sanctum::actingAs($this->listener);
        $this->postJson("{$this->url}/messages", ['body' => 'hi'])->assertJsonPath('cooldown_seconds', 30);
        $this->travel(10)->seconds();
        $this->postJson("{$this->url}/messages", ['body' => 'again'])->assertTooManyRequests();

        Sanctum::actingAs($staff);
        $this->postJson("{$this->url}/messages", ['body' => 'Studio here'])
            ->assertCreated()
            ->assertJsonPath('cooldown_seconds', 3)
            ->assertJsonPath('data.user.is_staff', true);
    });

    it('keeps cooldowns separate per station', function () {
        Station::factory()->create(['slug' => 'oz-bali']);
        Sanctum::actingAs($this->listener);

        $this->postJson("{$this->url}/messages", ['body' => 'Bandung'])->assertCreated();
        $this->postJson('/api/v1/stations/oz-bali/chat/messages', ['body' => 'Bali'])->assertCreated();
    });

    it('rejects muted listeners', function () {
        $this->listener->forceFill(['chat_muted_until' => now()->addMinutes(10)])->save();
        Sanctum::actingAs($this->listener);

        $this->postJson("{$this->url}/messages", ['body' => 'let me in'])->assertForbidden();
        expect(ChatMessage::count())->toBe(0);
    });

    it('rejects empty and over-long messages', function (string $body) {
        Sanctum::actingAs($this->listener);

        $this->postJson("{$this->url}/messages", ['body' => $body])->assertUnprocessable();
    })->with([
        'blank' => ['   '],
        'too long' => [str_repeat('a', 281)],
    ]);

    it('still saves the message when broadcasting fails', function () {
        Event::fake(); // replaced below with a throwing listener
        Event::swap(new class(app()) extends Dispatcher
        {
            public function dispatch($event, $payload = [], $halt = false)
            {
                if ($event instanceof ChatMessageSent) {
                    throw new RuntimeException('Reverb is down');
                }

                return parent::dispatch($event, $payload, $halt);
            }
        });
        Sanctum::actingAs($this->listener);

        $this->postJson("{$this->url}/messages", ['body' => 'still here'])->assertCreated();
        expect(ChatMessage::where('body', 'still here')->exists())->toBeTrue();
    });
});

describe('moderation', function () {
    it('pins one message per station and broadcasts the change', function () {
        $first = ChatMessage::factory()->for($this->station)->pinned()->create();
        $second = ChatMessage::factory()->for($this->station)->create();

        app(ModerateChatAction::class)->pin($second);

        expect($first->refresh()->is_pinned)->toBeFalse()
            ->and($second->refresh()->is_pinned)->toBeTrue();
        Event::assertDispatched(ChatMessagePinned::class, fn ($event) => $event->message->is($second));
    });

    it('soft deletes and broadcasts the deleted id', function () {
        $message = ChatMessage::factory()->for($this->station)->pinned()->create();

        app(ModerateChatAction::class)->delete($message);

        expect($message->refresh()->trashed())->toBeTrue()
            ->and($message->is_pinned)->toBeFalse();
        Event::assertDispatched(ChatMessageDeleted::class, fn ($event) => $event->broadcastWith() === ['id' => $message->id]);
    });

    it('mutes and unmutes listeners but never staff', function () {
        $this->seed(RolesAndPermissionsSeeder::class);
        $moderate = app(ModerateChatAction::class);

        $moderate->mute($this->listener, 60);
        expect($this->listener->refresh()->isChatMuted())->toBeTrue();

        $moderate->unmute($this->listener);
        expect($this->listener->refresh()->isChatMuted())->toBeFalse();

        $moderate->mute(User::factory()->create()->assignRole(AdminRole::SocialMedia->value), 60);
    })->throws(InvalidArgumentException::class, 'Staff accounts cannot be muted.');

    it('broadcasts slow mode changes', function () {
        app(ModerateChatAction::class)->setSlowMode($this->station, 10);

        expect($this->station->refresh()->chat_slow_mode_seconds)->toBe(10);
        Event::assertDispatched(ChatSettingsUpdated::class, fn ($event) => $event->broadcastWith()['settings']['cooldown_seconds'] === 10);
    });

    it('only lets the three admin roles moderate', function () {
        $this->seed(RolesAndPermissionsSeeder::class);

        foreach (AdminRole::cases() as $role) {
            expect(User::factory()->create()->assignRole($role->value)->can('moderate', ChatMessage::class))->toBeTrue();
        }

        expect($this->listener->can('moderate', ChatMessage::class))->toBeFalse();
    });
});

it('masks blocked words as whole words only', function (string $input, string $expected) {
    expect((new ProfanityFilter(['anjing', 'tai']))->clean($input))->toBe($expected);
})->with([
    'plain' => ['dasar anjing', 'dasar a*****'],
    'case' => ['ANJING!', 'A*****!'],
    'stretched' => ['anjiiiing', 'a********'],
    'inside a word' => ['santai dulu', 'santai dulu'],
    'clean' => ['Lagu favorit!', 'Lagu favorit!'],
]);
