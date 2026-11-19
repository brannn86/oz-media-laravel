<?php

namespace App\Http\Resources;

use App\Models\Event;
use App\Support\MediaUrl;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Event
 */
class EventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'organizer' => $this->organizer_name,
            'is_oz_event' => $this->is_oz_event,
            'location' => $this->location,
            'starts_at' => $this->start_date->toIso8601String(),
            'ends_at' => $this->end_date?->toIso8601String(),
            'description' => $this->description ? RichContentRenderer::make($this->description)->toHtml() : null,
            'poster_url' => MediaUrl::for($this->poster_image),
            'ticket_url' => $this->ticket_url,
        ];
    }
}
