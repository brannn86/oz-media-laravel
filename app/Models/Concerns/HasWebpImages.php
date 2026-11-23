<?php

namespace App\Models\Concerns;

use App\Support\MediaUrl;
use Illuminate\Database\Eloquent\Model;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Images stored with Spatie Media Library and converted to WebP on the
 * `media` queue (config/media-library.php `queue_name`).
 *
 * Each model lists its collections in webpImageCollections(): name =>
 * [max width, max height, legacy column]. The legacy column is the image path
 * saved before Media Library existed; it stays as a fallback so older
 * uploads keep showing until they are re-uploaded.
 *
 * @mixin Model
 */
trait HasWebpImages
{
    use InteractsWithMedia;

    /**
     * @return array<string, array{0: int, 1: int, 2: string|null}>
     */
    abstract protected function webpImageCollections(): array;

    public function registerMediaCollections(): void
    {
        foreach (array_keys($this->webpImageCollections()) as $collection) {
            $this->addMediaCollection($collection)
                ->singleFile()
                ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
        }
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        foreach ($this->webpImageCollections() as $collection => [$width, $height]) {
            $this->addMediaConversion('webp')
                ->performOnCollections($collection)
                ->format('webp')
                ->quality(80)
                ->fit(Fit::Max, $width, $height);

            $this->addMediaConversion('thumb')
                ->performOnCollections($collection)
                ->format('webp')
                ->quality(75)
                ->fit(Fit::Crop, 320, 320);
        }
    }

    /**
     * Public URL for a collection's image: the WebP version once it has been
     * generated, the original until then, the legacy column before that.
     */
    public function webpImageUrl(string $collection, string $conversion = 'webp'): ?string
    {
        $media = $this->getFirstMedia($collection);

        if ($media instanceof Media) {
            return $media->getAvailableUrl([$conversion]);
        }

        $legacy = $this->webpImageCollections()[$collection][2] ?? null;

        return $legacy ? MediaUrl::for($this->getAttribute($legacy)) : null;
    }
}
