<?php

namespace App\Models;

use App\Enums\PollStatus;
use App\Enums\PollType;
use App\Models\Concerns\BelongsToStation;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A live on-air poll (song battle, trivia or a plain question).
 *
 * @property int $id
 * @property int $station_id
 * @property int|null $created_by
 * @property string $question
 * @property PollType $type
 * @property PollStatus $status
 * @property CarbonImmutable|null $opened_at
 * @property CarbonImmutable|null $closed_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['station_id', 'created_by', 'question', 'type', 'status', 'opened_at', 'closed_at'])]
class Poll extends Model
{
    use BelongsToStation;

    protected function casts(): array
    {
        return [
            'type' => PollType::class,
            'status' => PollStatus::class,
            'opened_at' => 'immutable_datetime',
            'closed_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return HasMany<PollOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(PollOption::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return HasMany<PollVote, $this>
     */
    public function votes(): HasMany
    {
        return $this->hasMany(PollVote::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeLive(Builder $query): void
    {
        $query->where('status', PollStatus::Live);
    }

    public function isLive(): bool
    {
        return $this->status === PollStatus::Live;
    }
}
