<?php

use App\Actions\Polls\CastPollVoteAction;
use App\Actions\Polls\LaunchPollAction;
use App\Enums\PollStatus;
use App\Enums\PollType;
use App\Enums\SongRequestStatus;
use App\Jobs\AwardPoints;
use App\Models\Poll;
use App\Models\PollVote;
use App\Models\SongRequest;
use App\Models\Station;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    config(['broadcasting.default' => 'null']);
    $this->station = Station::factory()->create(['slug' => 'oz-bandung']);
});

describe('song request upvotes', function () {
    beforeEach(function () {
        $this->request = SongRequest::factory()->for($this->station)->create();
        $this->url = "/api/v1/stations/oz-bandung/requests/{$this->request->id}/upvote";
    });

    it('keeps the counter equal to the number of upvoters', function () {
        $users = User::factory()->count(5)->create();

        foreach ($users as $user) {
            Sanctum::actingAs($user);
            $this->postJson($this->url)->assertJsonPath('upvoted', true);
        }

        // Two change their mind.
        foreach ($users->take(2) as $user) {
            Sanctum::actingAs($user);
            $this->postJson($this->url)->assertJsonPath('upvoted', false);
        }

        $rows = DB::table('song_request_upvotes')->where('song_request_id', $this->request->id)->count();

        expect($rows)->toBe(3)
            ->and($this->request->refresh()->upvotes_count)->toBe(3);
    });

    it('never lets one listener count twice', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        foreach (range(1, 6) as $i) {
            $this->postJson($this->url)->assertJsonPath('upvoted', $i % 2 === 1);
        }

        expect($this->request->refresh()->upvotes_count)->toBe(0);

        // A duplicate row can't exist: the pivot key forbids it.
        DB::table('song_request_upvotes')->insert(['user_id' => $user->id, 'song_request_id' => $this->request->id]);
        expect(fn () => DB::table('song_request_upvotes')->insert(['user_id' => $user->id, 'song_request_id' => $this->request->id]))
            ->toThrow(UniqueConstraintViolationException::class);
    });

    it('never drops the counter below zero', function () {
        $user = User::factory()->create();
        // An upvote row whose count was lost (e.g. manual fix-up): removing it must not go negative.
        DB::table('song_request_upvotes')->insert(['user_id' => $user->id, 'song_request_id' => $this->request->id]);
        Sanctum::actingAs($user);

        $this->postJson($this->url)->assertJsonPath('upvoted', false);

        expect($this->request->refresh()->upvotes_count)->toBe(0);
    });

    it('refuses upvotes on closed, own or other-station requests', function () {
        $listener = User::factory()->create();
        Sanctum::actingAs($listener);

        $this->request->update(['status' => SongRequestStatus::Played]);
        $this->postJson($this->url)->assertUnprocessable();

        $own = SongRequest::factory()->for($this->station)->for($listener)->create();
        $this->postJson("/api/v1/stations/oz-bandung/requests/{$own->id}/upvote")->assertUnprocessable();

        $elsewhere = SongRequest::factory()->create();
        $this->postJson("/api/v1/stations/oz-bandung/requests/{$elsewhere->id}/upvote")->assertNotFound();
    });
});

describe('poll voting', function () {
    beforeEach(function () {
        $this->poll = app(LaunchPollAction::class)->handle($this->station, null, 'Which one?', PollType::SongBattle, [
            ['label' => 'A'], ['label' => 'B'],
        ]);
        $this->url = "/api/v1/stations/oz-bandung/polls/{$this->poll->id}/votes";
    });

    it('takes exactly one vote per listener, enforced by the database too', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson($this->url, ['option_id' => $this->poll->options[0]->id])->assertOk();
        $this->postJson($this->url, ['option_id' => $this->poll->options[1]->id])->assertUnprocessable();

        expect(fn () => PollVote::create(['poll_id' => $this->poll->id, 'poll_option_id' => $this->poll->options[1]->id, 'user_id' => $user->id]))
            ->toThrow(UniqueConstraintViolationException::class);
    });

    it('keeps option totals equal to the recorded votes', function () {
        foreach (User::factory()->count(7)->create() as $index => $user) {
            app(CastPollVoteAction::class)->handle($user, $this->poll, $this->poll->options[$index % 2]->id);
        }

        foreach ($this->poll->options()->get() as $option) {
            expect($option->votes_count)->toBe(PollVote::where('poll_option_id', $option->id)->count());
        }
    });

    it('refuses options from another poll and votes after closing', function () {
        $other = app(LaunchPollAction::class)->handle(Station::factory()->create(), null, 'Other?', PollType::General, [
            ['label' => 'X'], ['label' => 'Y'],
        ]);
        Sanctum::actingAs(User::factory()->create());

        $this->postJson($this->url, ['option_id' => $other->options[0]->id])->assertUnprocessable();

        // Relaunching on this station closes the current poll.
        app(LaunchPollAction::class)->handle($this->station, null, 'New?', PollType::General, [['label' => '1'], ['label' => '2']]);
        expect($this->poll->refresh()->status)->toBe(PollStatus::Closed);
        $this->postJson($this->url, ['option_id' => $this->poll->options[0]->id])->assertUnprocessable();
    });

    it('pays for a vote once, after it is committed, and never for a refused vote', function () {
        Queue::fake();
        $user = User::factory()->create();

        app(CastPollVoteAction::class)->handle($user, $this->poll, $this->poll->options[0]->id);

        try {
            app(CastPollVoteAction::class)->handle($user, $this->poll, $this->poll->options[1]->id);
        } catch (ValidationException) {
            // Expected: second vote refused.
        }

        Queue::assertPushed(AwardPoints::class, 1);
    });

    it('requires an account to vote', function () {
        $this->postJson($this->url, ['option_id' => $this->poll->options[0]->id])->assertUnauthorized();
        expect(Poll::find($this->poll->id)->votes()->count())->toBe(0);
    });
});
