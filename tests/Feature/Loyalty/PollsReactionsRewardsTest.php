<?php

use App\Actions\Points\AwardPointsAction;
use App\Actions\Polls\ClosePollAction;
use App\Actions\Polls\LaunchPollAction;
use App\Actions\Rewards\ManageRedemptionAction;
use App\Enums\PointReason;
use App\Enums\PollStatus;
use App\Enums\PollType;
use App\Enums\RedemptionStatus;
use App\Events\Live\PollUpdated;
use App\Events\Live\StudioReactionSent;
use App\Models\PointTransaction;
use App\Models\Poll;
use App\Models\Reward;
use App\Models\Station;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Event::fake([PollUpdated::class, StudioReactionSent::class]);
    $this->station = Station::factory()->create(['slug' => 'oz-bandung', 'stream_video_url' => 'https://youtu.be/abcdefghijk']);
    $this->listener = User::factory()->create();
});

function launchBattle(Station $station): Poll
{
    return app(LaunchPollAction::class)->handle($station, null, 'Which one tonight?', PollType::SongBattle, [
        ['label' => 'Tulus'],
        ['label' => 'Hindia'],
    ]);
}

describe('polls', function () {
    it('launches one live poll per station and broadcasts it', function () {
        $first = launchBattle($this->station);
        $second = launchBattle($this->station);

        expect($first->refresh()->status)->toBe(PollStatus::Closed)
            ->and($second->status)->toBe(PollStatus::Live);
        Event::assertDispatched(PollUpdated::class, fn ($event) => $event->broadcastOn()[0]->name === 'station.oz-bandung.polls');

        $this->getJson('/api/v1/stations/oz-bandung/polls/current')
            ->assertJsonPath('data.id', $second->id)
            ->assertJsonPath('my_vote', null);
    });

    it('takes one vote per listener, pays for it and returns live percentages', function () {
        $poll = launchBattle($this->station);
        [$tulus, $hindia] = $poll->options;
        Sanctum::actingAs($this->listener);
        $url = "/api/v1/stations/oz-bandung/polls/{$poll->id}/votes";

        $this->postJson($url, ['option_id' => $tulus->id])
            ->assertOk()
            ->assertJsonPath('data.total_votes', 1)
            ->assertJsonPath('data.options.0.percent', 100)
            ->assertJsonPath('my_vote', $tulus->id)
            ->assertJsonPath('points_balance', 5);

        $this->postJson($url, ['option_id' => $hindia->id])->assertUnprocessable();

        Sanctum::actingAs(User::factory()->create());
        $this->postJson($url, ['option_id' => $hindia->id])->assertJsonPath('data.options.1.percent', 50);

        expect(PointTransaction::where('reason', PointReason::PollVote)->count())->toBe(2);
    });

    it('refuses votes after closing and only then reveals the trivia answer', function () {
        $poll = app(LaunchPollAction::class)->handle($this->station, null, 'OZ Bandung first aired on?', PollType::Trivia, [
            ['label' => 'FM 103.1', 'is_correct' => true],
            ['label' => 'FM 99.9'],
        ]);
        Sanctum::actingAs($this->listener);

        $this->getJson('/api/v1/stations/oz-bandung/polls/current')->assertJsonPath('data.options.0.is_correct', null);

        app(ClosePollAction::class)->handle($poll);

        $this->postJson("/api/v1/stations/oz-bandung/polls/{$poll->id}/votes", ['option_id' => $poll->options[0]->id])
            ->assertUnprocessable();
        Event::assertDispatched(PollUpdated::class, fn ($event) => $event->broadcastWith()['poll']['options'][0]['is_correct'] === true);
    });

    it('requires exactly one correct answer for trivia', function () {
        app(LaunchPollAction::class)->handle($this->station, null, 'Q?', PollType::Trivia, [['label' => 'A'], ['label' => 'B']]);
    })->throws(ValidationException::class);
});

describe('reactions', function () {
    it('only work while the station offers video', function () {
        $this->station->update(['stream_video_url' => null]);
        $this->postJson('/api/v1/stations/oz-bandung/reactions', ['type' => 'fire'])->assertUnprocessable();

        $this->station->update(['stream_video_url' => 'https://www.youtube.com/watch?v=abcdefghijk']);
        $this->postJson('/api/v1/stations/oz-bandung/reactions', ['type' => 'fire'])->assertNoContent();

        Event::assertDispatched(StudioReactionSent::class, fn ($event) => $event->broadcastWith() === ['type' => 'fire']
            && $event->broadcastOn()[0]->name === 'station.oz-bandung.reactions');
    });

    it('are throttled per viewer', function () {
        Sanctum::actingAs($this->listener);

        foreach (range(1, 3) as $_) {
            $this->postJson('/api/v1/stations/oz-bandung/reactions', ['type' => 'heart'])->assertNoContent();
        }

        $this->postJson('/api/v1/stations/oz-bandung/reactions', ['type' => 'heart'])->assertTooManyRequests();
        $this->postJson('/api/v1/stations/oz-bandung/reactions', ['type' => 'nope'])->assertUnprocessable();
    });
});

describe('rewards', function () {
    beforeEach(function () {
        app(AwardPointsAction::class)->handle($this->listener, 300, PointReason::Adjustment);
        $this->reward = Reward::factory()->limited(1)->create(['name' => 'OZ T-shirt', 'points_cost' => 200]);
        $this->url = "/api/v1/stations/oz-bandung/rewards/{$this->reward->id}/redeem";
    });

    it('lists rewards for the station', function () {
        Reward::factory()->create(['station_id' => Station::factory()->create()->id, 'name' => 'Bali only']);
        Reward::factory()->create(['is_active' => false]);

        $this->getJson('/api/v1/stations/oz-bandung/rewards')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.stock_left', 1);
    });

    it('spends points and stock when redeeming', function () {
        Sanctum::actingAs($this->listener);

        $this->postJson($this->url, ['note' => 'Size L'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('points_balance', 100);

        expect($this->reward->refresh()->stock)->toBe(0);
        $this->postJson($this->url)->assertUnprocessable()->assertJsonValidationErrors('reward');
    });

    it('refuses when the balance is too low', function () {
        $this->reward->update(['points_cost' => 1000]);
        Sanctum::actingAs($this->listener);

        $this->postJson($this->url)->assertUnprocessable()->assertJsonValidationErrors('points');
        expect($this->reward->refresh()->stock)->toBe(1);
    });

    it('refunds points and stock when an admin cancels, but only once', function () {
        Sanctum::actingAs($this->listener);
        $this->postJson($this->url)->assertCreated();
        $redemption = $this->listener->redemptions()->firstOrFail();

        app(ManageRedemptionAction::class)->cancel($redemption);

        expect($redemption->status)->toBe(RedemptionStatus::Cancelled)
            ->and($this->listener->refresh()->points_balance)->toBe(300)
            ->and($this->reward->refresh()->stock)->toBe(1);

        app(ManageRedemptionAction::class)->fulfill($redemption);
    })->throws(ValidationException::class, 'already been handled');
});
