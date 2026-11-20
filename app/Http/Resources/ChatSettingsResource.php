<?php

namespace App\Http\Resources;

use App\Models\Station;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Chat settings a client needs to enforce cooldowns in the UI.
 *
 * @mixin Station
 */
class ChatSettingsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'channel' => $this->chatChannel(),
            'slow_mode_seconds' => $this->chat_slow_mode_seconds,
            // What a listener actually waits between messages.
            'cooldown_seconds' => max((int) config('oz.chat.min_interval_seconds'), $this->chat_slow_mode_seconds),
            'max_length' => (int) config('oz.chat.max_length'),
        ];
    }
}
