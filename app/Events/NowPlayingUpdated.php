<?php

namespace App\Events;

use App\Http\Resources\TrackPlayResource;
use App\Models\Station;
use App\Models\TrackPlay;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * The song on air changed (or the stream went quiet: track is null).
 */
class NowPlayingUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Station $station, public ?TrackPlay $track) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new Channel(StationStreamStatusUpdated::channelName($this->station))];
    }

    public function broadcastAs(): string
    {
        return 'now-playing.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'track' => $this->track ? TrackPlayResource::make($this->track)->resolve() : null,
        ];
    }
}
