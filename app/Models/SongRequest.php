<?php

namespace App\Models;

use App\Enums\SongRequestStatus;
use App\Models\Concerns\BelongsToStation;
use Carbon\CarbonImmutable;
use Database\Factories\SongRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property int $station_id
 * @property int $user_id
 * @property string $song_title
 * @property string $artist
 * @property string|null $shoutout_message
 * @property int $upvotes_count
 * @property SongRequestStatus $status
 * @property CarbonImmutable|null $decided_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['station_id', 'user_id', 'song_title', 'artist', 'shoutout_message', 'status'])]
class SongRequest extends Model
{
    /** @use HasFactory<SongRequestFactory> */
    use BelongsToStation, HasFactory;

    protected $attributes = [
        'status' => 'pending',
        'upvotes_count' => 0,
    ];

    protected function casts(): array
    {
        return [
            'status' => SongRequestStatus::class,
            'upvotes_count' => 'integer',
            'decided_at' => 'immutable_datetime',
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
     * @return BelongsToMany<User, $this>
     */
    public function upvoters(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'song_request_upvotes')->withPivot('created_at');
    }

    /**
     * The live queue: approved first (coming up), then most upvoted, then oldest.
     *
     * @param  Builder<static>  $query
     */
    public function scopeQueue(Builder $query): void
    {
        $query->whereIn('status', SongRequestStatus::openValues())
            ->orderByRaw('case when status = ? then 0 else 1 end', [SongRequestStatus::Approved->value])
            ->orderByDesc('upvotes_count')
            ->orderBy('id');
    }
}
