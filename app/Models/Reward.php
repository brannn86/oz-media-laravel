<?php

namespace App\Models;

use App\Models\Concerns\HasWebpImages;
use Carbon\CarbonImmutable;
use Database\Factories\RewardFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;

/**
 * Merch, tickets and other prizes listeners buy with OZ Poin.
 *
 * @property int $id
 * @property int|null $station_id Null = offered on every station.
 * @property string $name
 * @property string|null $description
 * @property string|null $image
 * @property int $points_cost
 * @property int|null $stock Null = unlimited.
 * @property bool $is_active
 * @property int $sort_order
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['station_id', 'name', 'description', 'image', 'points_cost', 'stock', 'is_active', 'sort_order'])]
class Reward extends Model implements HasMedia
{
    /** @use HasFactory<RewardFactory> */
    use HasFactory, HasWebpImages;

    protected function casts(): array
    {
        return [
            'points_cost' => 'integer',
            'stock' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Station, $this>
     */
    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    /**
     * @return HasMany<RewardRedemption, $this>
     */
    public function redemptions(): HasMany
    {
        return $this->hasMany(RewardRedemption::class);
    }

    /**
     * Active rewards a station's listeners can see (its own plus network-wide ones).
     *
     * @param  Builder<static>  $query
     */
    public function scopeAvailableOn(Builder $query, Station $station): void
    {
        $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('station_id')->orWhere('station_id', $station->id))
            ->orderBy('sort_order')
            ->orderBy('points_cost');
    }

    public function isInStock(): bool
    {
        return $this->stock === null || $this->stock > 0;
    }

    /**
     * Reward photo: WebP up to 1200x1200, falling back to the old `image` column.
     *
     * @return array<string, array{0: int, 1: int, 2: string|null}>
     */
    protected function webpImageCollections(): array
    {
        return ['image' => [1200, 1200, 'image']];
    }
}
