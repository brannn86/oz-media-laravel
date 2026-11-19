<?php

namespace App\Http\Resources;

use App\Models\Post;
use App\Support\MediaUrl;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * News article. The (sanitized) body is only included on the detail page.
 *
 * @mixin Post
 */
class PostResource extends JsonResource
{
    private bool $withBody = false;

    public function withBody(): static
    {
        $this->withBody = true;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'image_url' => MediaUrl::for($this->featured_image),
            'published_at' => $this->published_at?->toIso8601String(),
            'author' => $this->whenLoaded('author', fn () => $this->author?->name),
            'station' => $this->whenLoaded('station', fn () => ['slug' => $this->station->slug, 'name' => $this->station->name]),
            // Strips scripts/unsafe attributes even if an editor account is compromised.
            'body' => $this->when($this->withBody, fn () => RichContentRenderer::make($this->body)->toHtml()),
        ];
    }
}
