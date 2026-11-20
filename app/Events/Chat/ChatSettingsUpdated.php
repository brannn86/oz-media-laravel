<?php

namespace App\Events\Chat;

use App\Http\Resources\ChatSettingsResource;

/**
 * Slow mode changed: clients update their send cooldown.
 */
class ChatSettingsUpdated extends ChatEvent
{
    public function broadcastAs(): string
    {
        return 'chat.settings.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return ['settings' => ChatSettingsResource::make($this->station)->resolve()];
    }
}
