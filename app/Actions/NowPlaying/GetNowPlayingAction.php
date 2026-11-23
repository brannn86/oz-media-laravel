<?php

namespace App\Actions\NowPlaying;

use App\Models\Station;
use App\Models\TrackPlay;
use Illuminate\Support\Collection;

/**
 * The song on air and the ones before it, as recorded by SyncNowPlayingAction.
 */
class GetNowPlayingAction
{
    /**
     * @return array{track: TrackPlay|null, recent: Collection<int, TrackPlay>}
     */
    public function handle(Station $station, ?int $limit = null): array
    {
        $limit ??= (int) config('oz.now_playing.recent_limit', 10);

        $plays = $station->trackPlays()
            ->latest('started_at')
            ->latest('id')
            ->limit($limit + 1)
            ->get();

        $track = $plays->first(fn (TrackPlay $play): bool => $play->ended_at === null);

        return [
            'track' => $track,
            'recent' => $plays->reject(fn (TrackPlay $play): bool => $play->is($track))->take($limit)->values(),
        ];
    }
}
