<?php

namespace App\Actions\Analytics;

use App\Enums\AudienceMode;
use App\Models\Station;
use App\Models\User;
use App\Support\TrafficSource;
use App\Support\UserAgent;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Counts the people with the site's player on (guests included): every
 * player pings once a minute while the radio plays or the video is open.
 *
 * A ping is counted once per visitor per minute and mode, in the cache;
 * flush() (every minute, scheduled) writes each finished minute to
 * audience_minutes. Each visitor is also recorded once per WIB day in
 * audience_visitors. The database sees one write per minute per station
 * and mode, plus one per new visitor a day, whatever the audience size.
 *
 * Sessions (audience_sessions) are the exception: one row per unbroken
 * stretch of listening, updated by each counted ping, so session length,
 * drop-off and where listeners came from can be measured. A gap of
 * SESSION_GAP_SECONDS starts a new session.
 */
class RecordAudience
{
    /** How long a minute's count stays in the cache for flush() to pick up. */
    private const BUCKET_TTL = 900;

    /** Minutes flush() looks back over (re-writing is harmless: it sets, not adds). */
    private const FLUSH_WINDOW = 10;

    /** No ping for this long ends a session (pings come every 60 s). */
    public const SESSION_GAP_SECONDS = 180;

    /**
     * @return bool Whether this ping counted (false: already counted this minute).
     */
    /**
     * @param  array{referrer_host?: string|null, utm_source?: string|null, utm_medium?: string|null, utm_campaign?: string|null}  $landing  Where the visit came from.
     * @param  string  $platform  "Website" or "App".
     * @param  User|null  $listener  Signed in: their age range, gender and location label the session (the account itself isn't stored).
     */
    public function ping(Station $station, AudienceMode $mode, string $viewerKey, array $landing = [], ?string $userAgent = null, string $platform = 'Website', ?User $listener = null): bool
    {
        $visitor = sha1($viewerKey);
        $minute = CarbonImmutable::now()->startOfMinute();

        if (! Cache::add($this->seenKey($station, $mode, $minute, $visitor), true, 120)) {
            return false;
        }

        $count = $this->countKey($station, $mode, $minute);
        Cache::add($count, 0, self::BUCKET_TTL);
        Cache::increment($count);

        $date = CarbonImmutable::now('Asia/Jakarta')->toDateString();

        if (Cache::add("audience:visitor:{$station->id}:{$mode->value}:{$date}:{$visitor}", true, 2 * 86400)) {
            DB::table('audience_visitors')->insertOrIgnore([
                'station_id' => $station->id,
                'date' => $date,
                'mode' => $mode->value,
                'visitor' => $visitor,
            ]);
        }

        $this->trackSession($station, $mode, $visitor, $landing, $userAgent, $platform, $listener);

        return true;
    }

    /**
     * Extends the visitor's current session, or starts one (with its source).
     *
     * @param  array{referrer_host?: string|null, utm_source?: string|null, utm_medium?: string|null, utm_campaign?: string|null}  $landing
     */
    private function trackSession(Station $station, AudienceMode $mode, string $visitor, array $landing, ?string $userAgent, string $platform, ?User $listener): void
    {
        $key = "audience:session:{$station->id}:{$mode->value}:{$visitor}";
        $now = CarbonImmutable::now();
        $session = Cache::get($key);

        if (is_numeric($session) && DB::table('audience_sessions')->where('id', $session)->update([
            'last_seen_at' => $now,
            'minutes' => DB::raw('minutes + 1'),
        ]) === 1) {
            Cache::put($key, (int) $session, self::SESSION_GAP_SECONDS);

            return;
        }

        $clean = fn (string $field): ?string => filled($landing[$field] ?? null) ? Str::limit(trim((string) $landing[$field]), 100, '') : null;

        $id = DB::table('audience_sessions')->insertGetId([
            'station_id' => $station->id,
            'mode' => $mode->value,
            'platform' => $platform,
            'device' => UserAgent::device($userAgent),
            'os' => UserAgent::os($userAgent),
            'browser' => $platform === 'App' ? null : UserAgent::browser($userAgent),
            'age_range' => $listener?->age_range?->value,
            'gender' => $listener?->gender?->value,
            'location' => $listener?->location,
            'visitor' => $visitor,
            'started_at' => $now,
            'last_seen_at' => $now,
            'minutes' => 1,
            'source' => TrafficSource::classify($clean('referrer_host'), $clean('utm_source')),
            'referrer_host' => $clean('referrer_host'),
            'utm_source' => $clean('utm_source'),
            'utm_medium' => $clean('utm_medium'),
            'utm_campaign' => $clean('utm_campaign'),
        ]);

        Cache::put($key, $id, self::SESSION_GAP_SECONDS);
    }

    /**
     * Writes the finished minutes (not the current one) to audience_minutes.
     *
     * @return int Rows written.
     */
    public function flush(): int
    {
        $now = CarbonImmutable::now()->startOfMinute();
        $rows = [];

        foreach (Station::query()->pluck('id') as $stationId) {
            foreach (AudienceMode::cases() as $mode) {
                for ($ago = 1; $ago <= self::FLUSH_WINDOW; $ago++) {
                    $minute = $now->subMinutes($ago);
                    $count = Cache::get($this->countKey($stationId, $mode, $minute));

                    if (is_numeric($count) && (int) $count > 0) {
                        $rows[] = [
                            'station_id' => $stationId,
                            'minute_at' => $minute,
                            'mode' => $mode->value,
                            'listeners' => (int) $count,
                        ];
                    }
                }
            }
        }

        if ($rows !== []) {
            DB::table('audience_minutes')->upsert($rows, ['station_id', 'minute_at', 'mode'], ['listeners']);
        }

        return count($rows);
    }

    private function countKey(Station|int $station, AudienceMode $mode, CarbonImmutable $minute): string
    {
        $id = $station instanceof Station ? $station->id : $station;

        return "audience:count:{$id}:{$mode->value}:{$minute->getTimestamp()}";
    }

    private function seenKey(Station $station, AudienceMode $mode, CarbonImmutable $minute, string $visitor): string
    {
        return "audience:seen:{$station->id}:{$mode->value}:{$minute->getTimestamp()}:{$visitor}";
    }
}
