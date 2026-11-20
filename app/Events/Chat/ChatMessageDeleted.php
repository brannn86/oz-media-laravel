<?php

namespace App\Events\Chat;

use App\Models\Station;

class ChatMessageDeleted extends ChatEvent
{
    public function __construct(Station $station, public readonly int $messageId)
    {
        parent::__construct($station);
    }

    public function broadcastAs(): string
    {
        return 'chat.message.deleted';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return ['id' => $this->messageId];
    }
}
