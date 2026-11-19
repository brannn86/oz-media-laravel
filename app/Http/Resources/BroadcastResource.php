<?php

namespace App\Http\Resources;

use App\Actions\Broadcast\BroadcastStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * On-air now / up next, with ISO timestamps (WIB offset) the client uses for
 * progress bars and to know when to refresh.
 *
 * @property BroadcastStatus $resource
 */
class BroadcastResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = $this->resource;

        return [
            'at' => $status->at->toIso8601String(),
            'refresh_at' => $status->refreshAt()?->toIso8601String(),
            'current' => $status->current ? [
                ...ScheduleSlotResource::make($status->current)->resolve($request),
                'starts_at' => $status->currentStartsAt?->toIso8601String(),
                'ends_at' => $status->currentEndsAt?->toIso8601String(),
            ] : null,
            'next' => $status->next ? [
                ...ScheduleSlotResource::make($status->next)->resolve($request),
                'starts_at' => $status->nextStartsAt?->toIso8601String(),
            ] : null,
        ];
    }
}
