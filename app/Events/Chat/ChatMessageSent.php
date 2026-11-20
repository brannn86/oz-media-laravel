<?php

namespace App\Events\Chat;

use App\Http\Resources\ChatMessageResource;
use App\Models\ChatMessage;

class ChatMessageSent extends ChatEvent
{
    public function __construct(public readonly ChatMessage $message)
    {
        parent::__construct($message->station);
    }

    public function broadcastAs(): string
    {
        return 'chat.message.sent';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return ['message' => ChatMessageResource::make($this->message)->resolve()];
    }
}
