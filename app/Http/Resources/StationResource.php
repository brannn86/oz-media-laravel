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
    /** Shown above the contact details when the station hasn't set its own. */
    public const DEFAULT_CONTACT_HEADING = 'Got a song request? Say hi to the studio.';

    /** Station socials shown on the site, in order; empty ones are left out. */
    private const SOCIAL_NETWORKS = ['instagram', 'tiktok', 'youtube', 'threads', 'x'];

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'tagline' => $this->tagline,
            'contact' => $this->contact(),
            'social' => $this->socialProfiles(),
            // Off: the OZ Poin page and its links are hidden (points still accrue).
            'rewards_page' => $this->rewards_page_enabled,
            'channel' => StationStreamStatusUpdated::channelName($this->resource),
            'stream' => StreamStatusResource::make($this->resource)->resolve($request),
        ];
    }

    /**
     * Filled contact details. The map link is only kept when it's a web link,
     * since the address is rendered as a link to it.
     *
     * @return array<string, string>
     */
    private function contact(): array
    {
        $contact = array_filter($this->contact_info ?? [], filled(...));

        if (isset($contact['map_url']) && ! preg_match('#^https?://#i', (string) $contact['map_url'])) {
            unset($contact['map_url']);
        }

        $contact['heading'] ??= self::DEFAULT_CONTACT_HEADING;

        return $contact;
    }

    /**
     * The station's profiles in footer order; anything else stored is dropped.
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
