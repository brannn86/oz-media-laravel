<?php

namespace App\Http\Resources;

use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A chat message as listeners, Studio Mode and the mobile app see it.
 * Also the payload of the chat broadcast events.
 *
 * @mixin ChatMessage
 */
class ChatMessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'is_pinned' => $this->is_pinned,
            'created_at' => $this->created_at?->toIso8601String(),
            'user' => ListenerResource::make($this->user)->resolve($request),
        ];
    }
}
