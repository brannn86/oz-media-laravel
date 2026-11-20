<?php

namespace App\Models;

use App\Enums\PointReason;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row of the OZ Poin ledger. Never updated after it is written.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $station_id
 * @property int $points Positive when earned, negative when spent.
 * @property PointReason $reason
 * @property array<string, mixed>|null $meta
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['user_id', 'station_id', 'points', 'reason', 'meta'])]
class PointTransaction extends Model
{
    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'reason' => PointReason::class,
            'meta' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Station, $this>
     */
    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }
}
