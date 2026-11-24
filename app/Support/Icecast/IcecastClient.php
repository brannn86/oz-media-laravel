<?php

namespace App\Support\Icecast;

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Throwable;
use UnexpectedValueException;

/**
 * Reads the public status-json.xsl of an Icecast 2.4+ server.
 */
class IcecastClient
{
    /**
     * The source on `$mount`, or null when nothing is broadcasting there.
     *
     * @throws ConnectionException|RequestException|UnexpectedValueException when the server can't be read
     */
    public function source(string $statusUrl, string $mount): ?IcecastSource
    {
        $stats = Http::timeout((int) config('oz.now_playing.timeout_seconds', 5))
            ->acceptJson()
            ->get($statusUrl)
            ->throw()
            ->json('icestats');

        if (! is_array($stats)) {
            throw new UnexpectedValueException("{$statusUrl} did not return Icecast stats.");
        }

        // One mount is an object, several are a list; no mounts means no key at all.
        $sources = $stats['source'] ?? [];
        $sources = array_is_list($sources) ? $sources : [$sources];

        foreach ($sources as $source) {
            if (! is_array($source) || ! $this->isMount($source, $mount)) {
                continue;
            }

            $title = $source['title'] ?? null;

            return new IcecastSource(
                title: is_scalar($title) && trim((string) $title) !== '' ? trim((string) $title) : null,
                listeners: (int) ($source['listeners'] ?? 0),
                bitrate: isset($source['bitrate']) ? (int) $source['bitrate'] : null,
                listenerPeak: isset($source['listener_peak']) ? (int) $source['listener_peak'] : null,
                streamStartedAt: $this->time($source['stream_start_iso8601'] ?? null),
            );
        }

        return null;
    }

    private function time(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Matches on the path only: listenurl often carries the internal host/port
     * (http://host:8000/mount) while the public URL goes through a proxy.
     *
     * @param  array<string, mixed>  $source
     */
    private function isMount(array $source, string $mount): bool
    {
        $listenUrl = $source['listenurl'] ?? null;
        $path = is_string($listenUrl) ? parse_url($listenUrl, PHP_URL_PATH) : null;

        return is_string($path) && rtrim($path, '/') === $mount;
    }
}
