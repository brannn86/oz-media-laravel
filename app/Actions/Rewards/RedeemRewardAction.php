<?php

namespace App\Actions\Rewards;

use App\Actions\Points\SpendPointsAction;
use App\Enums\PointReason;
use App\Enums\RedemptionStatus;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\Station;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Trades OZ Poin for a reward. Locks the reward row so the last item in
 * stock can't be claimed twice, and spends points under the user lock.
 */
class RedeemRewardAction
{
    public function __construct(private readonly SpendPointsAction $spend) {}

    /**
     * @throws ValidationException when inactive, sold out, unavailable on this station or too expensive
     */
    public function handle(User $user, Reward $reward, Station $station, ?string $note = null): RewardRedemption
    {
        if (filled($note) && mb_strlen((string) $note) > 500) {
            throw ValidationException::withMessages(['note' => 'Keep the note under 500 characters.']);
        }

        return DB::transaction(function () use ($user, $reward, $station, $note): RewardRedemption {
            $locked = Reward::query()->whereKey($reward->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->is_active || ($locked->station_id !== null && $locked->station_id !== $station->id)) {
                throw ValidationException::withMessages(['reward' => 'This reward is not available.']);
            }

            if (! $locked->isInStock()) {
                throw ValidationException::withMessages(['reward' => 'Sorry, this reward just ran out.']);
            }

            $this->spend->handle($user, $locked->points_cost, PointReason::Redemption, $station, [
                'reward_id' => $locked->id,
            ]);

            if ($locked->stock !== null) {
                $locked->decrement('stock');
            }

            return $locked->redemptions()->create([
                'user_id' => $user->id,
                'points_spent' => $locked->points_cost,
                'status' => RedemptionStatus::Pending,
                'listener_note' => filled($note) ? trim((string) $note) : null,
            ]);
        });
    }
}
