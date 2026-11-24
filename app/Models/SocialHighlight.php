<?php

namespace App\Models;

use App\Enums\SocialPlatform;
use App\Enums\VideoOrientation;
use App\Models\Concerns\BelongsToStation;
use App\Models\Concerns\HasWebpImages;
use Carbon\CarbonImmutable;
use Database\Factories\SocialHighlightFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;

/**
 * @property int $id
 * @property int $station_id
 * @property SocialPlatform $platform
 * @property string $post_url
 * @property string $title
 * @property VideoOrientation|null $orientation Set by hand; null means detect it
 * @property string|null $custom_thumbnail
 * @property bool $is_featured
 * @property int $sort_order
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['station_id', 'platform', 'post_url', 'title', 'orientation', 'custom_thumbnail', 'is_featured', 'sort_order'])]
class SocialHighlight extends Model implements HasMedia
{
    /** @use HasFactory<SocialHighlightFactory> */
    use BelongsToStation, HasFactory, HasWebpImages;

    protected function casts(): array
    {
        return [
            'platform' => SocialPlatform::class,
            'orientation' => VideoOrientation::class,
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * The admin's choice, or a guess: YouTube Shorts links and every TikTok
     * and Instagram post are vertical; other YouTube links are long-form.
     */
    public function resolvedOrientation(): VideoOrientation
    {
        return $this->orientation ?? self::detectOrientation($this->platform, $this->post_url);
    }

    public static function detectOrientation(?SocialPlatform $platform, ?string $url): VideoOrientation
    {
        if ($platform !== SocialPlatform::YouTube) {
            return VideoOrientation::Portrait;
        }

        return str_starts_with(trim((string) parse_url((string) $url, PHP_URL_PATH), '/'), 'shorts/')
            ? VideoOrientation::Portrait
            : VideoOrientation::Landscape;
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeFeatured(Builder $query): void
    {
        $query->where('is_featured', true);
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderByDesc('id');
    }

    /**
     * Custom thumbnail: WebP up to 800x1000, falling back to the old `custom_thumbnail` column.
     *
     * @return array<string, array{0: int, 1: int, 2: string|null}>
     */
    protected function webpImageCollections(): array
    {
        return ['thumbnail' => [800, 1000, 'custom_thumbnail']];
    }
}
