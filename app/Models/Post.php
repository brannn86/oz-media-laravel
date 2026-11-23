<?php

namespace App\Models;

use App\Enums\PostStatus;
use App\Models\Concerns\BelongsToStation;
use App\Models\Concerns\HasUniqueSlug;
use App\Models\Concerns\HasWebpImages;
use Carbon\CarbonImmutable;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;

/**
 * An OZ News article.
 *
 * @property int $id
 * @property int $station_id
 * @property int|null $author_id
 * @property string $title
 * @property string $slug
 * @property string|null $excerpt
 * @property string $body
 * @property string|null $featured_image
 * @property PostStatus $status
 * @property CarbonImmutable|null $published_at
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'station_id', 'author_id', 'title', 'slug', 'excerpt', 'body', 'featured_image',
    'status', 'published_at', 'meta_title', 'meta_description',
])]
class Post extends Model implements HasMedia
{
    /** @use HasFactory<PostFactory> */
    use BelongsToStation, HasFactory, HasUniqueSlug, HasWebpImages;

    protected $attributes = [
        'status' => 'draft',
    ];

    protected static function booted(): void
    {
        // A published post always carries a publish date.
        static::saving(function (Post $post): void {
            if ($post->status === PostStatus::Published && $post->published_at === null) {
                $post->published_at = now();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'status' => PostStatus::class,
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<PostView, $this>
     */
    public function dailyViews(): HasMany
    {
        return $this->hasMany(PostView::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Published and not scheduled for the future.
     *
     * @param  Builder<static>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', PostStatus::Published)
            ->where('published_at', '<=', now());
    }

    public function isPublished(): bool
    {
        return $this->status === PostStatus::Published
            && $this->published_at?->isPast() === true;
    }

    /**
     * Article featured image: WebP up to 1920x1080, falling back to the old `featured_image` column.
     *
     * @return array<string, array{0: int, 1: int, 2: string|null}>
     */
    protected function webpImageCollections(): array
    {
        return ['featured' => [1920, 1080, 'featured_image']];
    }
}
