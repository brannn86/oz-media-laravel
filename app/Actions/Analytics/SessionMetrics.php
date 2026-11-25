<?php

namespace App\Actions\Analytics;

use App\Enums\AudienceMode;
use App\Models\TrackPlay;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * How long people stay, and what they leave during: from audience_sessions
 * (unbroken stretches of listening or watching on the site, guests included).
 *
 * Only finished sessions count (no ping for RecordAudience::SESSION_GAP_SECONDS),
 * so a session still going doesn't look short.
 */
class SessionMetrics
{
    private const TTL = 900;

    /** Length buckets, in minutes: [label, from, until (exclusive)]. */
    private const BUCKETS = [
        ['Under 5 min', 0, 5],
        ['5–15 min', 5, 15],
        ['15–30 min', 15, 30],
        ['30–60 min', 30, 60],
        ['1–2 h', 60, 120],
        ['2 h or more', 120, PHP_INT_MAX],
    ];

    /**
     * @return array{sessions: int, average: float, median: float, buckets: array<string, int>}
     */
    public function lengths(AudienceMode $mode, int $days): array
    {
        return Cache::remember("analytics:sessions:lengths:{$mode->value}:{$days}", self::TTL, function () use ($mode, $days): array {
            $minutes = DB::table('audience_sessions')
                ->where('mode', $mode->value)
                ->whereBetween('last_seen_at', $this->finished($days))
                ->orderBy('minutes')
                ->pluck('minutes')
                ->map(fn (mixed $value): int => (int) $value)
                ->all();

            $buckets = [];

            foreach (self::BUCKETS as [$label, $from, $until]) {
                $buckets[$label] = count(array_filter($minutes, fn (int $length): bool => $length >= $from && $length < $until));
            }

            $count = count($minutes);

            return [
                'sessions' => $count,
                'average' => $count > 0 ? round(array_sum($minutes) / $count, 1) : 0.0,
                'median' => $count > 0 ? (float) ($count % 2 === 1
                    ? $minutes[intdiv($count, 2)]
                    : ($minutes[$count / 2 - 1] + $minutes[$count / 2]) / 2) : 0.0,
                'buckets' => $buckets,
            ];
        });
    }

    /**
     * Songs radio listeners stopped during most, per time aired (songs aired at
     * least twice in the window, so one unlucky play doesn't top the list).
     *
     * @return list<array{title: string, artist: string|null, plays: int, tune_outs: int, per_play: float}>
     */
    public function songDropOff(int $days, int $limit = 10): array
    {
        return Cache::remember("analytics:sessions:songs:{$days}:{$limit}", self::TTL, function () use ($days, $limit): array {
            [$from, $until] = $this->finished($days);
            $songs = [];

            $plays = TrackPlay::query()
                ->whereBetween('started_at', [$from->subHours(2), $until])
                ->orderBy('station_id')
                ->orderBy('started_at')
                ->get(['station_id', 'title', 'artist', 'started_at', 'ended_at'])
                ->groupBy('station_id');

            foreach ($plays as $stationPlays) {
                foreach ($stationPlays as $play) {
                    if ($play->started_at->greaterThanOrEqualTo($from)) {
                        $key = $play->title."\0".$play->artist;
                        $songs[$key] ??= ['title' => $play->title, 'artist' => $play->artist, 'plays' => 0, 'tune_outs' => 0];
                        $songs[$key]['plays']++;
                    }
                }
            }

            $sessions = DB::table('audience_sessions')
                ->where('mode', AudienceMode::Audio->value)
                ->whereBetween('last_seen_at', [$from, $until])
                ->get(['station_id', 'last_seen_at']);

            // Per station, plays as a list sorted by start, for a binary search.
            $byStation = $plays->map(fn ($stationPlays): array => array_values($stationPlays->all()))->all();

            foreach ($sessions as $session) {
                $left = CarbonImmutable::parse((string) $session->last_seen_at);
                $play = $this->playingAt($byStation[$session->station_id] ?? [], $left);

                if ($play !== null) {
                    $key = $play->title."\0".$play->artist;
                    $songs[$key] ??= ['title' => $play->title, 'artist' => $play->artist, 'plays' => 1, 'tune_outs' => 0];
                    $songs[$key]['tune_outs']++;
                }
            }

            $rows = array_values(array_filter($songs, fn (array $song): bool => $song['plays'] >= 2 && $song['tune_outs'] > 0));

            foreach ($rows as &$row) {
                $row['per_play'] = round($row['tune_outs'] / $row['plays'], 2);
            }
            unset($row);

            usort($rows, fn (array $a, array $b): int => $b['per_play'] <=> $a['per_play'] ?: $b['tune_outs'] <=> $a['tune_outs']);

            return array_slice($rows, 0, $limit);
        });
    }

    /**
     * The play on air at $moment: the last one started by then, if not yet ended.
     *
     * @param  list<TrackPlay>  $plays  Sorted by started_at.
     */
    private function playingAt(array $plays, CarbonImmutable $moment): ?TrackPlay
    {
        $low = 0;
        $high = count($plays) - 1;
        $found = null;

        while ($low <= $high) {
            $middle = intdiv($low + $high, 2);

            if ($plays[$middle]->started_at->lessThanOrEqualTo($moment)) {
                $found = $plays[$middle];
                $low = $middle + 1;
            } else {
                $high = $middle - 1;
            }
        }

        return $found !== null && ($found->ended_at === null || $found->ended_at->greaterThan($moment)) ? $found : null;
    }

    /**
     * Sessions that ended in the window: from the start of the WIB day N-1 days
     * ago until the last moment a session can have finished by now.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function finished(int $days): array
    {
        $from = CarbonImmutable::now(DashboardMetrics::TIMEZONE)->startOfDay()->subDays($days - 1)->setTimezone(config('app.timezone'));

        return [$from, CarbonImmutable::now()->subSeconds(RecordAudience::SESSION_GAP_SECONDS)];
    }
}
