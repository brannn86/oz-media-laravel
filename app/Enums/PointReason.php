<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Why a ledger row exists. Earning reasons count towards leaderboards.
 */
enum PointReason: string implements HasLabel
{
    case Listening = 'listening';
    case StreakBonus = 'streak_bonus';
    case SongRequestApproved = 'song_request_approved';
    case PollVote = 'poll_vote';
    case Redemption = 'redemption';
    case RedemptionRefund = 'redemption_refund';
    case Adjustment = 'adjustment';

    public function getLabel(): string
    {
        return match ($this) {
            self::Listening => 'Listening',
            self::StreakBonus => 'Daily streak bonus',
            self::SongRequestApproved => 'Song request approved',
            self::PollVote => 'Poll vote',
            self::Redemption => 'Reward redeemed',
            self::RedemptionRefund => 'Redemption refunded',
            self::Adjustment => 'Adjustment by OZ',
        };
    }

    /**
     * @return list<string>
     */
    public static function earningValues(): array
    {
        return [
            self::Listening->value,
            self::StreakBonus->value,
            self::SongRequestApproved->value,
            self::PollVote->value,
        ];
    }
}
