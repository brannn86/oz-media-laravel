<?php

namespace App\Http\Resources;

use App\Models\Announcer;
use App\Models\Program;
use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Announcer
 */
class AnnouncerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'display_name' => $this->displayName(),
            'bio' => $this->bio,
            'avatar_url' => MediaUrl::for($this->avatar),
            'social' => array_filter($this->social_links ?? [], filled(...)),
            'programs' => $this->whenLoaded('programs', fn () => $this->programs
                ->map(fn (Program $program): array => ['id' => $program->id, 'title' => $program->title])
                ->values()
                ->all()),
        ];
    }
}
