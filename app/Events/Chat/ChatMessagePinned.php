<?php

namespace App\Events\Chat;

use App\Http\Resources\ChatMessageResource;
use App\Models\ChatMessage;

/**
 * Fired for both pin and unpin; `message.is_pinned` says which.
 * Only one message is pinned per station, so pinning replaces the previous one.
 */
class ChatMessagePinned extends ChatEvent
{
    public function __construct(public readonly ChatMessage $message)
    {
        parent::__construct($message->station);
    }

    public function broadcastAs(): string
    {
        return 'chat.message.pinned';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return ['message' => ChatMessageResource::make($this->message)->resolve()];
    }
}
