<?php

namespace App\Actions\NowPlaying;

use App\Actions\Concerns\BroadcastsSafely;
use App\Actions\SongRequests\ModerateSongRequestAction;
use App\Enums\SongRequestStatus;
use App\Events\ListenerCountUpdated;
use App\Events\NowPlayingUpdated;
use App\Models\SongRequest;
use App\Models\Station;
use App\Models\TrackPlay;
use App\Support\Icecast\IcecastClient;
use App\Support\Icecast\ListenerCount;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Brings a station's track_plays in line with what its Icecast stream reports.
 *
 * A new title closes the open play and starts another, tells every player over
 * Reverb, and marks open song requests for that song as played. An unreachable
 * server is logged once per outage; if it stays down past the grace period the
 * open play is closed, so the site never shows a stale song for long.
 *
 * Every read also refreshes the station's listener count, pushed to players
 * whenever it changes.
 */
class SyncNowPlayingAction
{
    use BroadcastsSafely;

    /** How long Icecast may be unreachable before the open play is closed. */
    public const OUTAGE_GRACE_SECONDS = 120;

    public function __construct(
        private readonly IcecastClient $icecast,
        private readonly ModerateSongRequestAction $moderate,
    ) {}

    /**
     * @return TrackPlay|null The play on air after syncing.
     */
    public function handle(Station $station): ?TrackPlay
    {
        $endpoint = $station->icecastStatusEndpoint();

        if ($endpoint === null) {
            return null;
        }

        $current = $station->trackPlays()->onAir()->latest('started_at')->first();

        try {
            $source = $this->icecast->source($endpoint['url'], $endpoint['mount']);
        } catch (Throwable $e) {
            return $this->handleOutage($station, $current, $e);
        }

        Cache::forget($this->outageKey($station));

        if (ListenerCount::put($station, $source)) {
            $this->broadcastSafely(new ListenerCountUpdated($station, $source?->listeners));
        }

        $raw = $source?->title;

        if ($raw === null) {
            // Mount offline or no metadata: nothing is on air.
            if ($current) {
                $this->close($current);
                $this->broadcastSafely(new NowPlayingUpdated($station, null));
            }

            return null;
        }

        if ($current?->raw_title === $raw) {
            return $current;
        }

        [$title, $artist] = $station->stream_title_format->parse($raw);

        $play = DB::transaction(function () use ($station, $current, $raw, $title, $artist, $source): TrackPlay {
            if ($current) {
                $this->close($current);
            }

            return $station->trackPlays()->create([
                'title' => Str::limit($title, 250, ''),
                'artist' => $artist !== null ? Str::limit($artist, 250, '') : null,
                'raw_title' => Str::limit($raw, 500, ''),
                'listeners' => $source->listeners,
                'started_at' => now(),
            ]);
        });

        $this->markRequestsPlayed($station, $play);
        $this->broadcastSafely(new NowPlayingUpdated($station, $play));

        return $play;
    }

    private function handleOutage(Station $station, ?TrackPlay $current, Throwable $e): ?TrackPlay
    {
        $since = Cache::get($this->outageKey($station));

        if ($since === null) {
            Cache::put($this->outageKey($station), now()->getTimestamp(), now()->addDay());
            Log::warning("Now playing: can't read Icecast for {$station->slug}.", ['error' => $e->getMessage()]);

            return $current;
        }

        if (now()->getTimestamp() - (int) $since < self::OUTAGE_GRACE_SECONDS) {
            return $current;
        }

        if (ListenerCount::put($station, null)) {
            $this->broadcastSafely(new ListenerCountUpdated($station, null));
        }

        if ($current) {
            $this->close($current);
            $this->broadcastSafely(new NowPlayingUpdated($station, null));

            return null;
        }

        return $current;
    }

    private function close(TrackPlay $play): void
    {
        $play->update(['ended_at' => now()]);
    }

    /**
     * Every open request for this song (several listeners may ask for the same one).
     */
    private function markRequestsPlayed(Station $station, TrackPlay $play): void
    {
        $title = self::normalize($play->title);
        $artist = self::normalize($play->artist ?? '');

        if ($title === '') {
            return;
        }

        $station->songRequests()
            ->whereIn('status', SongRequestStatus::openValues())
            ->with('user', 'station')
            ->get()
            ->filter(fn (SongRequest $request): bool => self::normalize($request->song_title) === $title
                && self::sameArtist(self::normalize($request->artist), $artist))
            ->each(function (SongRequest $request): void {
                try {
                    $this->moderate->markPlayed($request);
                } catch (ValidationException) {
                    // A moderator got there first.
                }
            });
    }

    /**
     * "Salma Salsabil" matches "Salma Salsabil feat. Juicy Luicy" either way round.
     * A track with no artist only needs the title to match.
     */
    private static function sameArtist(string $requested, string $played): bool
    {
        if ($played === '' || $requested === '') {
            return true;
        }

        return str_contains(" {$played} ", " {$requested} ") || str_contains(" {$requested} ", " {$played} ");
    }

    /**
     * Lowercase ASCII words, bracketed extras like "(Radio Edit)" dropped.
     */
    public static function normalize(string $value): string
    {
        $value = preg_replace('/[(\[][^)\]]*[)\]]/u', ' ', $value) ?? $value;
        $value = Str::lower(Str::ascii($value));

        return trim((string) preg_replace('/[^a-z0-9]+/', ' ', $value));
    }

    private function outageKey(Station $station): string
    {
        return "now-playing:outage:{$station->id}";
    }
}
