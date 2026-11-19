<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Fills an empty `slug` from the model's source attribute and keeps it unique.
 *
 * Models with a `station_id` get slugs unique per station; others are unique globally.
 *
 * @mixin Model
 */
trait HasUniqueSlug
{
    public static function bootHasUniqueSlug(): void
    {
        static::saving(function (Model $model): void {
            /** @var Model&self $model */
            $base = Str::slug((string) ($model->getAttribute('slug') ?: $model->getAttribute($model->slugSourceAttribute())));

            if ($base === '') {
                $base = Str::lower(Str::random(8));
            }

            $model->setAttribute('slug', $model->uniqueSlug($base));
        });
    }

    protected function slugSourceAttribute(): string
    {
        return 'title';
    }

    protected function uniqueSlug(string $base): string
    {
        $slug = $base;
        $suffix = 2;

        while ($this->slugQuery()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    /**
     * @return Builder<static>
     */
    protected function slugQuery(): Builder
    {
        $query = static::query();

        if ($this->exists) {
            $query->whereKeyNot($this->getKey());
        }

        if (in_array('station_id', $this->getFillable(), true)) {
            $query->where('station_id', $this->getAttribute('station_id'));
        }

        return $query;
    }
}
