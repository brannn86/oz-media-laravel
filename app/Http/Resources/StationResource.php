<?php

namespace App\Http\Resources;

use App\Events\StationStreamStatusUpdated;
use App\Models\Station;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public station data shared with every page under /{station}.
 *
 * @mixin Station
 */
class StationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'tagline' => $this->tagline,
            'contact' => array_filter($this->contact_info ?? [], filled(...)),
            'social' => array_filter($this->social_links ?? [], filled(...)),
            'channel' => StationStreamStatusUpdated::channelName($this->resource),
            'stream' => StreamStatusResource::make($this->resource)->resolve($request),
        ];
    }
}
