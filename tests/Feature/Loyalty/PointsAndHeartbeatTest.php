<?php

use App\Actions\Points\AwardPointsAction;
use App\Actions\Points\SpendPointsAction;
use App\Enums\PointReason;
use App\Models\PointTransaction;
use App\Models\Station;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->station = Station::factory()->create(['slug' => 'oz-bandung']);
    $this->user = User::factory()->create();
    $this->url = '/api/v1/stations/oz-bandung/heartbeat';
    // Mid-morning WIB so a day boundary isn't crossed by accident.
    $this->travelTo(Carbon::parse('2026-11-16 10:00', 'Asia/Jakarta'));
});

function balanceMatchesLedger(User $user): bool
{
    return $user->refresh()->points_balance === (int) PointTransaction::where('user_id', $user->id)->sum('points');
}

describe('heartbeat', function () {
    it('needs an account', function () {
        $this->postJson($this->url)->assertUnauthorized();
    });

    it('pays a minute of listening plus the first streak bonus', function () {
        Sanctum::actingAs($this->user);

        $this->postJson($this->url)
            ->assertOk()
            ->assertJson([
                'accepted' => true,
                'listening_points' => 1,
                'streak_bonus' => 5,
                'streak_days' => 1,
                'points_balance' => 6,
            ]);

        expect(balanceMatchesLedger($this->user))->toBeTrue();
    });

    it('ignores extra heartbeats inside the interval, whatever tab or station sends them', function () {
        Station::factory()->create(['slug' => 'oz-bali']);
        Sanctum::actingAs($this->user);

        $this->postJson($this->url)->assertJsonPath('accepted', true);
        $this->postJson($this->url)->assertJsonPath('accepted', false)->assertJsonPath('awarded', 0);
        $this->postJson('/api/v1/stations/oz-bali/heartbeat')->assertJsonPath('accepted', false);

        $this->travel(56)->seconds();
        $this->postJson($this->url)->assertJsonPath('accepted', true)->assertJsonPath('listening_points', 1);

        expect($this->user->refresh()->points_balance)->toBe(7);
    });

    it('stops listening points at the daily cap but keeps accepting heartbeats', function () {
        config(['oz.points.daily_listening_cap' => 3]);
        Sanctum::actingAs($this->user);

        foreach (range(1, 4) as $minute) {
            $response = $this->postJson($this->url);
            $this->travel(60)->seconds();
        }

        $response->assertJsonPath('accepted', true)
            ->assertJsonPath('listening_points', 0)
            ->assertJsonPath('daily_cap_reached', true);

        expect((int) PointTransaction::where('reason', PointReason::Listening)->sum('points'))->toBe(3);
    });

    it('grows the streak on consecutive WIB days and resets after a missed day', function () {
        Sanctum::actingAs($this->user);

        $this->postJson($this->url)->assertJsonPath('streak_days', 1);

        $this->travel(1)->days();
        $this->postJson($this->url)->assertJsonPath('streak_days', 2)->assertJsonPath('streak_bonus', 10);

        $this->travel(2)->days();
        $this->postJson($this->url)->assertJsonPath('streak_days', 1)->assertJsonPath('streak_bonus', 5);
    });

    it('counts the WIB day, not UTC', function () {
        Sanctum::actingAs($this->user);

        // 23:50 WIB Monday, then 00:10 WIB Tuesday: two different days (same UTC day).
        $this->travelTo(Carbon::parse('2026-11-16 23:50', 'Asia/Jakarta'));
        $this->postJson($this->url)->assertJsonPath('streak_days', 1);

        $this->travelTo(Carbon::parse('2026-11-17 00:10', 'Asia/Jakarta'));
        $this->postJson($this->url)->assertJsonPath('streak_days', 2);
    });

    it('caps the streak bonus', function () {
        $this->user->forceFill(['current_streak_days' => 20, 'last_listened_date' => '2026-11-15'])->save();
        Sanctum::actingAs($this->user);

        $this->postJson($this->url)->assertJsonPath('streak_days', 21)->assertJsonPath('streak_bonus', 35);
    });
});

describe('ledger', function () {
    it('keeps the balance equal to the ledger when spending', function () {
        app(AwardPointsAction::class)->handle($this->user, 50, PointReason::Adjustment);
        app(SpendPointsAction::class)->handle($this->user, 20, PointReason::Redemption);

        expect($this->user->points_balance)->toBe(30)
            ->and(balanceMatchesLedger($this->user))->toBeTrue();
    });

    it('refuses to overspend', function () {
        app(AwardPointsAction::class)->handle($this->user, 10, PointReason::Adjustment);

        app(SpendPointsAction::class)->handle($this->user, 11, PointReason::Redemption);
    })->throws(ValidationException::class);

    it('shows a listener their own ledger', function () {
        app(AwardPointsAction::class)->handle($this->user, 10, PointReason::PollVote, $this->station);
        Sanctum::actingAs($this->user);

        $this->getJson('/api/v1/me/points')
            ->assertOk()
            ->assertJsonPath('points_balance', 10)
            ->assertJsonPath('data.0.reason_label', 'Poll vote')
            ->assertJsonPath('data.0.station', $this->station->name);
    });
});

it('ranks listeners by points earned on the station, this week and all time', function () {
    $award = app(AwardPointsAction::class);
    $rina = User::factory()->create(['name' => 'Rina']);
    $budi = User::factory()->create(['name' => 'Budi']);

    $award->handle($rina, 40, PointReason::Listening, $this->station);
    $award->handle($budi, 30, PointReason::Listening, $this->station);
    $award->handle($budi, 999, PointReason::Listening, Station::factory()->create()); // another station
    $award->handle($budi, 500, PointReason::Adjustment, $this->station); // not earned by listening

    $this->travelTo(now()->subWeeks(2));
    $award->handle($budi, 100, PointReason::Listening, $this->station);
    $this->travelBack();
    $this->travelTo(Carbon::parse('2026-11-16 10:00', 'Asia/Jakarta'));

    $this->getJson('/api/v1/stations/oz-bandung/leaderboard')
        ->assertJsonPath('data.0.user.name', 'Rina')
        ->assertJsonPath('data.0.points', 40)
        ->assertJsonPath('data.1.points', 30);

    $this->getJson('/api/v1/stations/oz-bandung/leaderboard?period=all')
        ->assertJsonPath('data.0.user.name', 'Budi')
        ->assertJsonPath('data.0.points', 130);
});
