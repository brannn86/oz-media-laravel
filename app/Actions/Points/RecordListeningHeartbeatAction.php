<?php

namespace App\Actions\Points;

use App\Enums\PointReason;
use App\Models\PointTransaction;
use App\Models\Station;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Rewards a minute of listening. Players call it every 60 seconds while
 * audio or video is actually playing (POST /api/v1/stations/{slug}/heartbeat).
 *
 * Anti-farming:
 * - A cache lock keyed by *account* (not tab, device or station) admits one
 *   heartbeat per interval, so extra tabs earn nothing extra. On Redis the
 *   lock is atomic across all web servers.
 * - Listening points stop at a daily cap (WIB day).
 *
 * The first accepted heartbeat of a WIB day also advances the listening
 * streak and pays the streak bonus.
 */
class RecordListeningHeartbeatAction
{
    public const TIMEZONE = 'Asia/Jakarta';

    public function __construct(private readonly AwardPointsAction $award) {}

    public function handle(User $user, Station $station): HeartbeatResult
    {
        $interval = (int) config('oz.points.heartbeat_interval_seconds');

        // Acquired and deliberately never released: it expires after the interval.
        if (! Cache::lock("heartbeat:user:{$user->id}", $interval)->get()) {
            return new HeartbeatResult(
                accepted: false,
                listeningPoints: 0,
                streakBonus: 0,
                streakDays: $user->current_streak_days,
                balance: $user->points_balance,
                dailyCapReached: false,
                nextInSeconds: $interval,
            );
        }

        return DB::transaction(function () use ($user, $station, $interval): HeartbeatResult {
            $today = CarbonImmutable::now(self::TIMEZONE)->startOfDay();

            $streakBonus = $this->advanceStreak($user, $station, $today);
            [$listening, $capReached] = $this->awardListening($user, $station, $today);

            return new HeartbeatResult(
                accepted: true,
                listeningPoints: $listening,
                streakBonus: $streakBonus,
                streakDays: $user->current_streak_days,
                balance: $user->points_balance,
                dailyCapReached: $capReached,
                nextInSeconds: $interval,
            );
        });
    }

    /**
     * @return int The streak bonus paid (0 if already counted today).
     */
    private function advanceStreak(User $user, Station $station, CarbonImmutable $today): int
    {
        $last = $user->last_listened_date;

        if ($last !== null && $last->isSameDay($today)) {
            return 0;
        }

        $continues = $last !== null && $last->isSameDay($today->subDay());

        $user->forceFill([
            'current_streak_days' => $continues ? $user->current_streak_days + 1 : 1,
            'last_listened_date' => $today->toDateString(),
        ])->save();

        $bonus = (int) config('oz.points.streak_bonus_per_day')
            * min($user->current_streak_days, (int) config('oz.points.streak_bonus_max_days'));

        if ($bonus > 0) {
            $this->award->handle($user, $bonus, PointReason::StreakBonus, $station, [
                'streak_days' => $user->current_streak_days,
            ]);
        }

        return $bonus;
    }

    /**
     * @return array{0: int, 1: bool} Points awarded, and whether today's cap is now reached.
     */
    private function awardListening(User $user, Station $station, CarbonImmutable $today): array
    {
        $cap = (int) config('oz.points.daily_listening_cap');

        $earnedToday = (int) PointTransaction::query()
            ->where('user_id', $user->id)
            ->where('reason', PointReason::Listening)
            ->where('created_at', '>=', $today->setTimezone(config('app.timezone')))
            ->sum('points');

        $points = max(0, min((int) config('oz.points.per_heartbeat'), $cap - $earnedToday));

        if ($points > 0) {
            $this->award->handle($user, $points, PointReason::Listening, $station);
        }

        return [$points, $earnedToday + $points >= $cap];
    }
}
