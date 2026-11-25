<?php

use App\Models\Station;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-11-25 12:00', 'Asia/Jakarta'));
    $this->station = Station::factory()->create();
});

function listenedOn(string $at): void
{
    DB::table('audience_sessions')->insert([
        'station_id' => test()->station->id,
        'mode' => 'audio',
        'visitor' => sha1($at),
        'started_at' => $at,
        'last_seen_at' => $at,
        'minutes' => 1,
        'source' => 'Direct',
    ]);

    DB::table('audience_visitors')->insert([
        'station_id' => test()->station->id,
        'date' => Carbon::parse($at)->toDateString(),
        'mode' => 'audio',
        'visitor' => sha1($at),
    ]);
}

it('deletes per-visitor rows older than the stated retention and keeps the rest', function () {
    config(['oz.retention.visitor_analytics_months' => 13]);

    listenedOn('2025-10-01 10:00');
    listenedOn('2025-10-26 10:00');
    listenedOn('2026-11-20 10:00');

    $this->artisan('analytics:prune-visitors')->assertSuccessful();

    expect(DB::table('audience_sessions')->pluck('started_at')->map(fn ($at) => substr((string) $at, 0, 10))->all())
        ->toBe(['2025-10-26', '2026-11-20'])
        ->and(DB::table('audience_visitors')->orderBy('date')->pluck('date')->map(fn ($d) => substr((string) $d, 0, 10))->all())
        ->toBe(['2025-10-26', '2026-11-20']);
});

it('runs every day', function () {
    $this->artisan('schedule:list')->expectsOutputToContain('analytics:prune-visitors');
});
