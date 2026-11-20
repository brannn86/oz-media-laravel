<?php

namespace App\Http\Resources;

use App\Models\PointTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PointTransaction
 */
class PointTransactionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'points' => $this->points,
            'reason' => $this->reason->value,
            'reason_label' => $this->reason->getLabel(),
            'station' => $this->whenLoaded('station', fn () => $this->station?->name),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
