<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStation;
use Carbon\CarbonImmutable;
use Database\Factories\TrackPlayFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One song as reported by the station's Icecast stream.
 *
 * @property int $id
 * @property int $station_id
 * @property string $title
 * @property string|null $artist
 * @property string $raw_title
 * @property int|null $listeners Icecast listener count when the song started.
 * @property CarbonImmutable $started_at
 * @property CarbonImmutable|null $ended_at Null while on air.
 */
#[Fillable(['station_id', 'title', 'artist', 'raw_title', 'listeners', 'started_at', 'ended_at'])]
class TrackPlay extends Model
{
    /** @use HasFactory<TrackPlayFactory> */
    use BelongsToStation, HasFactory;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'listeners' => 'integer',
            'started_at' => 'immutable_datetime',
            'ended_at' => 'immutable_datetime',
        ];
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeOnAir(Builder $query): void
    {
        $query->whereNull('ended_at');
    }
}
