<?php

namespace App\Events\Live;

use App\Enums\ReactionType;
use App\Models\Station;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A floating reaction over the video livestream. Anonymous by design.
 */
class StudioReactionSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public readonly Station $station,
        public readonly ReactionType $type,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new Channel($this->station->reactionsChannel())];
    }

    public function broadcastAs(): string
    {
        return 'reaction.sent';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return ['type' => $this->type->value];
    }
}
