<?php

namespace App\Http\Resources;

use App\Models\Reward;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Reward
 */
class RewardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'image_url' => $this->webpImageUrl('image'),
            'points_cost' => $this->points_cost,
            // Exact counts only when low, so listeners know to hurry.
            'stock_left' => $this->stock !== null && $this->stock <= 10 ? $this->stock : null,
            'in_stock' => $this->isInStock(),
        ];
    }
}
