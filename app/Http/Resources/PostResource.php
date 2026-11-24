<?php

namespace App\Http\Resources;

use App\Models\Post;
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
        $card = [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'category' => $this->category?->toArray(),
            'excerpt' => $this->excerpt,
            'image_url' => $this->webpImageUrl('featured'),
            'published_at' => $this->published_at?->toIso8601String(),
            'author' => $this->whenLoaded('author', fn () => $this->author?->name),
            'station' => $this->whenLoaded('station', fn () => ['slug' => $this->station->slug, 'name' => $this->station->name]),
        ];

        if (! $this->withBody) {
            return $card;
        }

        return [
            ...$card,
            'updated_at' => $this->wasUpdatedAfterPublishing() ? $this->updated_at?->toIso8601String() : null,
            'image_caption' => $this->image_caption,
            'image_credit' => $this->image_credit,
            'source' => $this->source_name || $this->source_url ? [
                'name' => $this->source_name,
                // Only web links; anything else is shown as plain text.
                'url' => preg_match('#^https?://#i', (string) $this->source_url) ? $this->source_url : null,
            ] : null,
            // Strips scripts/unsafe attributes even if an editor account is compromised.
            'body' => RichContentRenderer::make($this->body)->toHtml(),
        ];
    }
}
