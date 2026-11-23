<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStation;
use App\Models\Concerns\HasUniqueSlug;
use App\Models\Concerns\HasWebpImages;
use Carbon\CarbonImmutable;
use Database\Factories\ProgramFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;

/**
 * @property int $id
 * @property int $station_id
 * @property string $title
 * @property string $slug
 * @property string|null $description
 * @property string|null $cover_image
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['station_id', 'title', 'slug', 'description', 'cover_image', 'is_active'])]
class Program extends Model implements HasMedia
{
    /** @use HasFactory<ProgramFactory> */
    use BelongsToStation, HasFactory, HasUniqueSlug, HasWebpImages;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<ProgramSchedule, $this>
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(ProgramSchedule::class)
            ->orderBy('day_of_week')
            ->orderBy('start_time');
    }

    /**
     * @return BelongsToMany<Announcer, $this>
     */
    public function announcers(): BelongsToMany
    {
        return $this->belongsToMany(Announcer::class);
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Program cover art: WebP up to 1600x1600, falling back to the old `cover_image` column.
     *
     * @return array<string, array{0: int, 1: int, 2: string|null}>
     */
    protected function webpImageCollections(): array
    {
        return ['cover' => [1600, 1600, 'cover_image']];
    }
}
