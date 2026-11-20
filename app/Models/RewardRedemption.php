<?php

namespace App\Models;

use App\Enums\RedemptionStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $reward_id
 * @property int $user_id
 * @property int $points_spent
 * @property RedemptionStatus $status
 * @property string|null $listener_note
 * @property string|null $admin_note
 * @property CarbonImmutable|null $fulfilled_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['reward_id', 'user_id', 'points_spent', 'status', 'listener_note', 'admin_note', 'fulfilled_at'])]
class RewardRedemption extends Model
{
    protected $attributes = [
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'status' => RedemptionStatus::class,
            'points_spent' => 'integer',
            'fulfilled_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Reward, $this>
     */
    public function reward(): BelongsTo
    {
        return $this->belongsTo(Reward::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
