<?php

use App\Actions\Analytics\SessionMetrics;
use App\Enums\AudienceMode;
use App\Models\Station;
use App\Models\TrackPlay;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-11-24 20:00', 'Asia/Jakarta'));
    $this->station = Station::factory()->create();
});

function finishedSession(int $minutes, CarbonInterface $lastSeen, string $mode = 'audio'): void
{
    DB::table('audience_sessions')->insert([
        'station_id' => test()->station->id,
        'mode' => $mode,
        'visitor' => sha1((string) random_int(1, PHP_INT_MAX)),
        'started_at' => $lastSeen->toImmutable()->subMinutes($minutes),
        'last_seen_at' => $lastSeen,
        'minutes' => $minutes,
        'source' => 'Direct',
    ]);
}

it('sums up how long finished sessions last', function () {
    foreach ([2, 8, 20, 45, 90, 200] as $minutes) {
        finishedSession($minutes, now()->subHour());
    }
    finishedSession(3, now()->subMinute()); // Maybe still going: not counted.

    $lengths = app(SessionMetrics::class)->lengths(AudienceMode::Audio, 1);

    expect($lengths['sessions'])->toBe(6)
        ->and($lengths['median'])->toBe(32.5)
        ->and($lengths['average'])->toBe(round(365 / 6, 1))
        ->and(array_values($lengths['buckets']))->toBe([1, 1, 1, 1, 1, 1]);
});

it('ranks songs by tune-outs per time aired', function () {
    $play = fn (string $title, string $start, string $end) => TrackPlay::query()->create([
        'station_id' => $this->station->id,
        'title' => $title,
        'artist' => 'Artist',
        'raw_title' => "{$title} - Artist",
        'listeners' => 10,
        'started_at' => Carbon::parse("2026-11-24 {$start}", 'Asia/Jakarta'),
        'ended_at' => Carbon::parse("2026-11-24 {$end}", 'Asia/Jakarta'),
    ]);

    $play('Skipper', '10:00', '10:04');
    $play('Keeper', '10:04', '10:08');
    $play('Skipper', '11:00', '11:04');
    $play('Keeper', '11:04', '11:08');
    $play('Once', '12:00', '12:04'); // Aired once: left out.

    foreach (['10:01', '10:02', '11:03', '11:05', '12:01'] as $time) {
        finishedSession(10, Carbon::parse("2026-11-24 {$time}", 'Asia/Jakarta'));
    }

    $songs = app(SessionMetrics::class)->songDropOff(1);

    expect(array_column($songs, 'title'))->toBe(['Skipper', 'Keeper'])
        ->and($songs[0])->toMatchArray(['plays' => 2, 'tune_outs' => 3, 'per_play' => 1.5]);
});
