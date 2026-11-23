<?php

namespace App\Console\Commands;

use App\Actions\NowPlaying\SyncNowPlayingAction;
use App\Models\Station;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('stations:sync-now-playing {station?* : Station slugs (default: every active station)}')]
#[Description("Read each station's Icecast stream and record the song on air")]
class SyncNowPlayingCommand extends Command
{
    public function handle(SyncNowPlayingAction $sync): int
    {
        $slugs = (array) $this->argument('station');

        Station::query()
            ->active()
            ->whereNotNull('stream_audio_url')
            ->when($slugs !== [], fn ($query) => $query->whereIn('slug', $slugs))
            ->each(function (Station $station) use ($sync): void {
                $play = $sync->handle($station);

                $this->line(sprintf(
                    '%s: %s',
                    $station->slug,
                    $play ? trim("{$play->title} / {$play->artist}", ' /') : '(nothing on air)',
                ));
            });

        return self::SUCCESS;
    }
}
