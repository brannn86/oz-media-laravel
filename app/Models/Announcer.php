<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStation;
use App\Models\Concerns\HasUniqueSlug;
use Carbon\CarbonImmutable;
use Database\Factories\AnnouncerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property int $station_id
 * @property string $name
 * @property string|null $stage_name
 * @property string $slug
 * @property string|null $bio
 * @property string|null $avatar
 * @property array<string, string|null>|null $social_links
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['station_id', 'name', 'stage_name', 'slug', 'bio', 'avatar', 'social_links', 'is_active'])]
class Announcer extends Model
{
    /** @use HasFactory<AnnouncerFactory> */
    use BelongsToStation, HasFactory, HasUniqueSlug;

    protected function casts(): array
    {
        return [
            'social_links' => 'array',
            'is_active' => 'boolean',
        ];
    }

    protected function slugSourceAttribute(): string
    {
        return filled($this->stage_name) ? 'stage_name' : 'name';
    }

    /**
     * @return BelongsToMany<Program, $this>
     */
    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class);
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function displayName(): string
    {
        return $this->stage_name ?: $this->name;
    }
}
