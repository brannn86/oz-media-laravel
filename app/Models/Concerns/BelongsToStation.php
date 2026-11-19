<?php

namespace App\Models\Concerns;

use App\Models\Station;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin Model
 */
trait BelongsToStation
{
    /**
     * @return BelongsTo<Station, $this>
     */
    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeForStation(Builder $query, Station|int $station): void
    {
        $query->where($this->qualifyColumn('station_id'), $station instanceof Station ? $station->getKey() : $station);
    }
}
