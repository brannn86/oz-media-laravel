<?php

namespace App\Events\Live;

use App\Http\Resources\PollResource;
use App\Models\Poll;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A poll was launched, received votes, or closed. Carries the full results.
 */
class PollUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public readonly Poll $poll) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new Channel($this->poll->station->pollsChannel())];
    }

    public function broadcastAs(): string
    {
        return 'poll.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return ['poll' => PollResource::make($this->poll->loadMissing('options'))->resolve()];
    }
}
