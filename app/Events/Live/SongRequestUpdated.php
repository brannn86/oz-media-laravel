<?php

namespace App\Events\Live;

use App\Http\Resources\SongRequestResource;
use App\Models\SongRequest;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A request was added, upvoted or moved (approved, played, rejected).
 * Clients upsert it, and drop it from the queue once it is no longer open.
 */
class SongRequestUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public readonly SongRequest $request) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new Channel($this->request->station->requestsChannel())];
    }

    public function broadcastAs(): string
    {
        return 'song-request.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return ['request' => SongRequestResource::make($this->request)->resolve()];
    }
}
