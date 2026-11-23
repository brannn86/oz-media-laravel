<?php

use App\Enums\PointReason;
use App\Jobs\AwardPoints;
use App\Models\PointTransaction;
use App\Models\Station;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->bandung = Station::factory()->create(['slug' => 'oz-bandung']);
    $this->bali = Station::factory()->create(['slug' => 'oz-bali']);
    $this->user = User::factory()->create();
    $this->travelTo(Carbon::parse('2026-11-16 10:00', 'Asia/Jakarta'));
});

function beat(string $station = 'oz-bandung')
{
    return test()->postJson("/api/v1/stations/{$station}/heartbeat");
}

it('needs an account', function () {
    beat()->assertUnauthorized();
});

it('pays one minute no matter how many tabs, devices or stations send heartbeats', function () {
    Sanctum::actingAs($this->user);

    // Five tabs + a phone + the other station, all in the same minute.
    $responses = collect([
        beat(), beat(), beat(), beat(), beat(),
        beat('oz-bali'),
        $this->withToken($this->user->createToken('phone')->plainTextToken)->postJson('/api/v1/stations/oz-bandung/heartbeat'),
    ]);

    expect($responses->filter(fn ($r) => $r->json('accepted'))->count())->toBe(1)
        ->and((int) PointTransaction::where('reason', PointReason::Listening)->sum('points'))->toBe(1);
});

it('keeps accounts independent', function () {
    Sanctum::actingAs($this->user);
    beat()->assertJsonPath('accepted', true);

    Sanctum::actingAs(User::factory()->create());
    beat()->assertJsonPath('accepted', true);
});

it('accepts again only after the interval', function () {
    Sanctum::actingAs($this->user);

    beat()->assertJsonPath('accepted', true);
    $this->travel(30)->seconds();
    beat()->assertJsonPath('accepted', false)->assertJsonPath('next_in_seconds', 55);
    $this->travel(30)->seconds();
    beat()->assertJsonPath('accepted', true);
});

it('enforces the daily cap from Redis even while ledger writes are still queued', function () {
    Queue::fake();
    config(['oz.points.daily_listening_cap' => 2]);
    Sanctum::actingAs($this->user);

    foreach (range(1, 4) as $_) {
        beat();
        $this->travel(60)->seconds();
    }

    // Nothing written yet (queued), but only 2 listening awards were ever queued.
    expect(PointTransaction::count())->toBe(0);
    Queue::assertPushed(AwardPoints::class, fn (AwardPoints $job) => $job->reason === PointReason::Listening && $job->points === 1);
    Queue::assertPushed(AwardPoints::class, fn (AwardPoints $job) => $job->reason === PointReason::Listening, 2);
});

it('writes points through the points queue', function () {
    Queue::fake();
    Sanctum::actingAs($this->user);

    beat()->assertJsonPath('points_balance', 6); // 1 listening + 5 streak, shown before the queue runs

    Queue::assertPushedOn('points', AwardPoints::class);
});

it('never pays the daily streak twice, even if Redis is flushed mid-day', function () {
    Sanctum::actingAs($this->user);

    beat()->assertJsonPath('streak_bonus', 5);
    Cache::flush(); // e.g. a Redis restart wipes the "already listened today" flag
    $this->travel(60)->seconds();
    beat()->assertJsonPath('accepted', true)->assertJsonPath('streak_bonus', 0)->assertJsonPath('streak_days', 1);

    expect(PointTransaction::where('reason', PointReason::StreakBonus)->count())->toBe(1);
});

it('keeps the balance equal to the ledger after a day of listening', function () {
    Sanctum::actingAs($this->user);

    foreach (range(1, 10) as $_) {
        beat();
        $this->travel(60)->seconds();
    }

    expect($this->user->refresh()->points_balance)->toBe((int) PointTransaction::where('user_id', $this->user->id)->sum('points'))
        ->and($this->user->points_balance)->toBe(15); // 10 minutes + 5 streak
});

it('rate-limits hammering the endpoint', function () {
    Sanctum::actingAs($this->user);

    foreach (range(1, 10) as $_) {
        beat();
    }

    beat()->assertTooManyRequests();
});
