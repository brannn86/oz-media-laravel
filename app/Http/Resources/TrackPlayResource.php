<?php

namespace App\Http\Resources;

use App\Actions\Broadcast\GetCurrentBroadcastAction;
use App\Models\TrackPlay;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TrackPlay
 */
class TrackPlayResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'artist' => $this->artist,
            'started_at' => $this->started_at->setTimezone(GetCurrentBroadcastAction::TIMEZONE)->toIso8601String(),
            'ended_at' => $this->ended_at?->setTimezone(GetCurrentBroadcastAction::TIMEZONE)->toIso8601String(),
        ];
    }
}
