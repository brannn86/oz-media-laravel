<?php

namespace App\Support\Icecast;

use App\Models\Station;
use Illuminate\Support\Facades\Cache;

/**
 * What Icecast says about a station's audience right now, as last read by
 * the now-playing sync (every 15 seconds): listeners on the mount, and its
 * peak since the stream (re)started. It expires on its own if the sync
 * stops, so nothing shows a stale count for long.
 *
 * These are stream listeners (every player: the site, apps, smart
 * speakers), not the site's own presence count.
 */
class ListenerCount
{
    private const TTL_SECONDS = 120;

    public static function get(Station $station): ?int
    {
        return self::stats($station)['listeners'] ?? null;
    }

    /**
     * @return array{listeners: int, peak: int|null, since: string|null}|null
     */
    public static function stats(Station $station): ?array
    {
        $stats = Cache::get(self::key($station));

        if (! is_array($stats) || ! is_numeric($stats['listeners'] ?? null)) {
            return null;
        }

        return [
            'listeners' => (int) $stats['listeners'],
            'peak' => is_numeric($stats['peak'] ?? null) ? (int) $stats['peak'] : null,
            'since' => is_string($stats['since'] ?? null) ? $stats['since'] : null,
        ];
    }

    /**
     * Stores what the mount reports (null: mount offline or Icecast unreadable).
     *
     * @return bool Whether the listener count changed.
     */
    public static function put(Station $station, ?IcecastSource $source): bool
    {
        $changed = self::get($station) !== $source?->listeners;

        if ($source === null) {
            Cache::forget(self::key($station));
        } else {
            Cache::put(self::key($station), [
                'listeners' => $source->listeners,
                'peak' => $source->listenerPeak,
                'since' => $source->streamStartedAt?->toIso8601String(),
            ], self::TTL_SECONDS);
        }

        return $changed;
    }

    private static function key(Station $station): string
    {
        return "icecast:listeners:{$station->id}";
    }
}
