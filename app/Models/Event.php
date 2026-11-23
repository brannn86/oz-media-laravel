<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStation;
use App\Models\Concerns\HasUniqueSlug;
use App\Models\Concerns\HasWebpImages;
use Carbon\CarbonImmutable;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;

/**
 * @property int $id
 * @property int $station_id
 * @property string $title
 * @property string $slug
 * @property string|null $organizer_name
 * @property bool $is_oz_event
 * @property string|null $location
 * @property CarbonImmutable $start_date
 * @property CarbonImmutable|null $end_date
 * @property string|null $description
 * @property string|null $poster_image
 * @property string|null $ticket_url
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'station_id', 'title', 'slug', 'organizer_name', 'is_oz_event', 'location',
    'start_date', 'end_date', 'description', 'poster_image', 'ticket_url',
])]
class Event extends Model implements HasMedia
{
    /** @use HasFactory<EventFactory> */
    use BelongsToStation, HasFactory, HasUniqueSlug, HasWebpImages;

    protected function casts(): array
    {
        return [
            'is_oz_event' => 'boolean',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
        ];
    }

    /**
     * Events that have not finished yet, soonest first.
     *
     * @param  Builder<static>  $query
     */
    public function scopeUpcoming(Builder $query): void
    {
        $query->where(fn (Builder $q) => $q
            ->where('end_date', '>=', now())
            ->orWhere(fn (Builder $q) => $q->whereNull('end_date')->where('start_date', '>=', now()->startOfDay())))
            ->orderBy('start_date');
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopePast(Builder $query): void
    {
        $query->where(fn (Builder $q) => $q
            ->where('end_date', '<', now())
            ->orWhere(fn (Builder $q) => $q->whereNull('end_date')->where('start_date', '<', now()->startOfDay())))
            ->orderByDesc('start_date');
    }

    /**
     * Event poster: WebP up to 1200x1600, falling back to the old `poster_image` column.
     *
     * @return array<string, array{0: int, 1: int, 2: string|null}>
     */
    protected function webpImageCollections(): array
    {
        return ['poster' => [1200, 1600, 'poster_image']];
    }
}
