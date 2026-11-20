<?php

namespace App\Actions\Points;

use App\Enums\PointReason;
use App\Models\PointTransaction;
use App\Models\Station;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * The only way OZ Poin are added: writes the ledger row and moves the
 * balance in one transaction, so points_balance always equals the ledger.
 */
class AwardPointsAction
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function handle(User $user, int $points, PointReason $reason, ?Station $station = null, array $meta = []): PointTransaction
    {
        if ($points <= 0) {
            throw new InvalidArgumentException('Awards must be positive; use SpendPointsAction to take points.');
        }

        return DB::transaction(function () use ($user, $points, $reason, $station, $meta): PointTransaction {
            $transaction = $user->pointTransactions()->create([
                'station_id' => $station?->id,
                'points' => $points,
                'reason' => $reason,
                'meta' => $meta ?: null,
            ]);

            $user->newQuery()->whereKey($user->getKey())->increment('points_balance', $points);
            $user->points_balance += $points;
            $user->syncOriginalAttribute('points_balance');

            return $transaction;
        });
    }
}
