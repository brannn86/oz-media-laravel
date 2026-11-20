<?php

namespace App\Actions\Points;

use App\Enums\PointReason;
use App\Models\PointTransaction;
use App\Models\Station;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Takes OZ Poin from a balance. Locks the user row so two simultaneous
 * redemptions can never spend the same points twice.
 */
class SpendPointsAction
{
    /**
     * @param  array<string, mixed>  $meta
     *
     * @throws ValidationException when the balance is too low
     */
    public function handle(User $user, int $points, PointReason $reason, ?Station $station = null, array $meta = []): PointTransaction
    {
        if ($points <= 0) {
            throw new InvalidArgumentException('Spend a positive number of points.');
        }

        return DB::transaction(function () use ($user, $points, $reason, $station, $meta): PointTransaction {
            $locked = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->points_balance < $points) {
                throw ValidationException::withMessages([
                    'points' => "You need {$points} OZ Poin but have {$locked->points_balance}.",
                ]);
            }

            $transaction = $locked->pointTransactions()->create([
                'station_id' => $station?->id,
                'points' => -$points,
                'reason' => $reason,
                'meta' => $meta ?: null,
            ]);

            $locked->decrement('points_balance', $points);

            $user->points_balance = $locked->points_balance;
            $user->syncOriginalAttribute('points_balance');

            return $transaction;
        });
    }
}
