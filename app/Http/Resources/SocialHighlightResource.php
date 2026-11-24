<?php

namespace App\Http\Resources;

use App\Enums\SocialPlatform;
use App\Models\SocialHighlight;
use App\Support\VideoStream;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SocialHighlight
 */
class SocialHighlightResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'platform' => $this->platform->value,
            'platform_label' => $this->platform->getLabel(),
            'url' => $this->post_url,
            'title' => $this->title,
            'orientation' => $this->resolvedOrientation()->value,
            'thumbnail_url' => $this->webpImageUrl('thumbnail') ?? $this->platformThumbnail(),
        ];
    }

    /**
     * YouTube exposes thumbnails publicly; TikTok and Instagram need an API, so they fall back to a styled tile.
     */
    private function platformThumbnail(): ?string
    {
        if ($this->platform !== SocialPlatform::YouTube) {
            return null;
        }

        $id = VideoStream::youTubeVideoId($this->post_url);

        return $id ? "https://i.ytimg.com/vi/{$id}/hqdefault.jpg" : null;
    }
}
