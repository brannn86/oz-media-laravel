<?php

namespace App\Events;

use App\Models\Station;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Icecast's listener count for the station changed (null: unknown right now).
 */
class ListenerCountUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Station $station, public ?int $listeners) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new Channel(StationStreamStatusUpdated::channelName($this->station))];
    }

    public function broadcastAs(): string
    {
        return 'listeners.updated';
    }

    /**
     * @return array{listeners: int|null}
     */
    public function broadcastWith(): array
    {
        return ['listeners' => $this->listeners];
    }
}
