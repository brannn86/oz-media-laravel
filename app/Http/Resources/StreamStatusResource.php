<?php

namespace App\Http\Resources;

use App\Models\Station;
use App\Support\VideoStream;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Everything the public player needs. Also the Reverb broadcast payload.
 *
 * @mixin Station
 */
class StreamStatusResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $video = VideoStream::describe($this->stream_video_url);

        return [
            'station' => $this->slug,
            'audio_url' => $this->stream_audio_url,
            'video' => $video,
            // Kept for API clients: video is offered whenever a video URL is set.
            'is_video_live' => $video !== null,
            // Fills the video player while the livestream is blank or offline.
            'intermission_url' => $video !== null ? $this->intermissionVideoUrl() : null,
        ];
    }
}
