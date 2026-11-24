<?php

namespace App\Observers;

use App\Events\StationStreamStatusUpdated;
use App\Models\Station;

class StationObserver
{
    /**
     * Attributes the public player depends on.
     */
    private const STREAM_ATTRIBUTES = ['stream_audio_url', 'stream_video_url'];

    public function updated(Station $station): void
    {
        if ($station->wasChanged(self::STREAM_ATTRIBUTES)) {
            StationStreamStatusUpdated::dispatch($station);
        }
    }
}
