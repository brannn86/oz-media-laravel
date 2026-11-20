<?php

namespace App\Http\Resources;

use App\Models\SongRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SongRequest
 */
class SongRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'song_title' => $this->song_title,
            'artist' => $this->artist,
            'shoutout_message' => $this->shoutout_message,
            'upvotes_count' => $this->upvotes_count,
            'status' => $this->status->value,
            'status_label' => $this->status->getLabel(),
            'created_at' => $this->created_at?->toIso8601String(),
            'user' => ListenerResource::make($this->user)->resolve($request),
        ];
    }
}
