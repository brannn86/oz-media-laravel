<?php

namespace App\Events\Chat;

use App\Models\Station;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Base for live chat events on the public `station.{slug}.chat` channel.
 *
 * Broadcast immediately (not queued) so chat stays instant even when the
 * queue is busy; the chat actions catch broadcast failures so a Reverb
 * outage never loses a message.
 */
abstract class ChatEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public readonly Station $station) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new Channel($this->station->chatChannel())];
    }
}
