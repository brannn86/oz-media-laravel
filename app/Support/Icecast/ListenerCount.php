<?php

namespace App\Support\Icecast;

use App\Models\Station;
use Illuminate\Support\Facades\Cache;

/**
 * How many people Icecast says are listening to a station's mount right now,
 * as last read by the now-playing sync (every 15 seconds). It expires on its
 * own if the sync stops, so the site never shows a stale count for long.
 */
class ListenerCount
{
    private const TTL_SECONDS = 120;

    public static function get(Station $station): ?int
    {
        $count = Cache::get(self::key($station));

        // Redis stores plain numbers unserialized, so they come back as strings.
        return is_numeric($count) ? (int) $count : null;
    }

    /**
     * Stores the count (null: mount offline or Icecast unreadable).
     *
     * @return bool Whether it changed.
     */
    public static function put(Station $station, ?int $count): bool
    {
        $changed = self::get($station) !== $count;

        if ($count === null) {
            Cache::forget(self::key($station));
        } else {
            Cache::put(self::key($station), $count, self::TTL_SECONDS);
        }

        return $changed;
    }

    private static function key(Station $station): string
    {
        return "icecast:listeners:{$station->id}";
    }
}
