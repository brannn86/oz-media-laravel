<?php

namespace App\Events;

use App\Http\Resources\StreamStatusResource;
use App\Models\Station;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Pushes stream changes (live mode, stream URLs) to every open player via Reverb.
 *
 * Fired by StationObserver, so any change path (Filament toggle, settings page,
 * tinker) reaches listeners.
 */
class StationStreamStatusUpdated implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Station $station) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new Channel(self::channelName($this->station))];
    }

    public function broadcastAs(): string
    {
        return 'stream-status.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return StreamStatusResource::make($this->station)->resolve();
    }

    public static function channelName(Station $station): string
    {
        return "station.{$station->slug}";
    }
}
