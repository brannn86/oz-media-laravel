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
    /** Station socials shown on the site, in order. */
    private const SOCIAL_NETWORKS = ['instagram', 'tiktok', 'youtube', 'threads'];

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
            'social' => $this->socialProfiles(),
            'channel' => StationStreamStatusUpdated::channelName($this->resource),
            'stream' => StreamStatusResource::make($this->resource)->resolve($request),
        ];
    }

    /**
     * The station's profiles in footer order; older networks (e.g. X) are dropped.
     *
     * @return array<string, string>
     */
    private function socialProfiles(): array
    {
        $profiles = [];

        foreach (self::SOCIAL_NETWORKS as $network) {
            $url = $this->social_links[$network] ?? null;

            if (is_string($url) && filled($url)) {
                $profiles[$network] = $url;
            }
        }

        return $profiles;
    }
}
