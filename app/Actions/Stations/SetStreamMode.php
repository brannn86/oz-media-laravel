<?php

namespace App\Actions\Stations;

use App\Models\Station;
use Illuminate\Validation\ValidationException;

/**
 * Switches a station between Audio Only and Video Livestream mode.
 */
class SetStreamMode
{
    /**
     * @throws ValidationException when video mode is requested without a video stream URL
     */
    public function handle(Station $station, bool $videoLive): Station
    {
        if ($videoLive && blank($station->stream_video_url)) {
            throw ValidationException::withMessages([
                'is_video_live' => 'Set a video stream URL in Station Settings before going live on video.',
            ]);
        }

        if ($station->is_video_live === $videoLive) {
            return $station;
        }

        // StationObserver broadcasts the change to open players.
        $station->forceFill(['is_video_live' => $videoLive])->save();

        return $station;
    }

    public function toggle(Station $station): Station
    {
        return $this->handle($station, ! $station->is_video_live);
    }
}
