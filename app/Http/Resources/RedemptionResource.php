<?php

namespace App\Http\Resources;

use App\Models\RewardRedemption;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A listener's own redemption (never shows the admin note).
 *
 * @mixin RewardRedemption
 */
class RedemptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reward' => $this->whenLoaded('reward', fn () => ['id' => $this->reward->id, 'name' => $this->reward->name]),
            'points_spent' => $this->points_spent,
            'status' => $this->status->value,
            'status_label' => $this->status->getLabel(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
