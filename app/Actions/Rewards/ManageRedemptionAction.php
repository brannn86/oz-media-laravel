<?php

namespace App\Actions\Rewards;

use App\Actions\Points\AwardPointsAction;
use App\Enums\PointReason;
use App\Enums\RedemptionStatus;
use App\Models\RewardRedemption;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Admin follow-up on a redemption: hand it over, or cancel it (refunding
 * the points and returning the item to stock).
 */
class ManageRedemptionAction
{
    public function __construct(private readonly AwardPointsAction $award) {}

    public function fulfill(RewardRedemption $redemption, ?string $adminNote = null): RewardRedemption
    {
        $this->moveFromPending($redemption, RedemptionStatus::Fulfilled, $adminNote, ['fulfilled_at' => now()]);

        return $redemption->refresh();
    }

    public function cancel(RewardRedemption $redemption, ?string $adminNote = null): RewardRedemption
    {
        DB::transaction(function () use ($redemption, $adminNote): void {
            $this->moveFromPending($redemption, RedemptionStatus::Cancelled, $adminNote);

            $redemption->loadMissing('reward', 'user');

            if ($redemption->points_spent > 0) {
                $this->award->handle($redemption->user, $redemption->points_spent, PointReason::RedemptionRefund, null, [
                    'redemption_id' => $redemption->id,
                ]);
            }

            if ($redemption->reward->stock !== null) {
                $redemption->reward->increment('stock');
            }
        });

        return $redemption->refresh();
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function moveFromPending(RewardRedemption $redemption, RedemptionStatus $to, ?string $adminNote, array $extra = []): void
    {
        $changed = RewardRedemption::query()
            ->whereKey($redemption->getKey())
            ->where('status', RedemptionStatus::Pending->value)
            ->update([
                'status' => $to->value,
                'admin_note' => $adminNote ?? $redemption->admin_note,
                'updated_at' => now(),
                ...$extra,
            ]);

        if ($changed === 0) {
            throw ValidationException::withMessages(['status' => 'This redemption has already been handled.']);
        }
    }
}
