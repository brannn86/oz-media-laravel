<?php

namespace App\Http\Resources;

use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Program
 */
class ProgramResource extends JsonResource
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
            'description' => $this->description,
            'cover_image_url' => $this->webpImageUrl('cover'),
            'announcers' => $this->whenLoaded('announcers', fn () => AnnouncerResource::collection($this->announcers)->resolve($request)),
        ];
    }
}
