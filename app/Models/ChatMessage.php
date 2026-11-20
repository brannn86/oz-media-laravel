<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStation;
use Carbon\CarbonImmutable;
use Database\Factories\ChatMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A live chat message on a station. Deleting (moderation) is a soft delete.
 *
 * @property int $id
 * @property int $station_id
 * @property int $user_id
 * @property string $body
 * @property bool $is_pinned
 * @property CarbonImmutable|null $deleted_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['station_id', 'user_id', 'body', 'is_pinned'])]
class ChatMessage extends Model
{
    /** @use HasFactory<ChatMessageFactory> */
    use BelongsToStation, HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
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
     * @param  Builder<static>  $query
     */
    public function scopePinned(Builder $query): void
    {
        $query->where('is_pinned', true);
    }
}
