<?php

namespace App\Support;

use App\Models\Station;
use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Per-station read cache for the public site and the on-air engine, so a
 * traffic spike during a live event is served from Redis, not PostgreSQL.
 *
 * Invalidation is by version: every key embeds the station's current cache
 * version, and StationContentObserver bumps it whenever anything the public
 * site shows for that station changes. Old entries are never read again and
 * simply expire. No cache tags needed, so it works on any store.
 */
class StationCache
{
    /** Safety net: even without a change, entries refresh this often. */
    public const DEFAULT_TTL = 3600;

    /**
     * Part of every key. Bump it when a cached payload changes shape (a new
     * field in StationResource, say), so a deploy doesn't serve entries
     * cached by the old code.
     */
    private const FORMAT = 5;

    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public static function remember(Station|int $station, string $name, Closure $callback, int $ttl = self::DEFAULT_TTL): mixed
    {
        $id = $station instanceof Station ? $station->getKey() : $station;

        return Cache::remember(self::key($id, $name), $ttl, $callback);
    }

    public static function flush(Station|int|null $station): void
    {
        if ($station === null) {
            return;
        }

        $id = $station instanceof Station ? $station->getKey() : $station;

        // A fresh random version (not an increment) also survives a cache restart.
        Cache::forever(self::versionKey($id), bin2hex(random_bytes(6)));
    }

    private static function key(int|string $stationId, string $name): string
    {
        return "station:{$stationId}:f".self::FORMAT.':v'.self::version($stationId).":{$name}";
    }

    private static function version(int|string $stationId): string
    {
        $version = Cache::get(self::versionKey($stationId));

        if (! is_string($version)) {
            $version = bin2hex(random_bytes(6));
            Cache::forever(self::versionKey($stationId), $version);
        }

        return $version;
    }

    private static function versionKey(int|string $stationId): string
    {
        return "station:{$stationId}:cache-version";
    }
}
