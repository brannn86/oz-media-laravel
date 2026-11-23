<?php

namespace App\Actions\Points;

use App\Enums\PointReason;
use App\Jobs\AwardPoints;
use App\Models\Station;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

/**
 * Rewards a minute of listening. Players call it every 60 seconds while
 * audio or video is actually playing (POST /api/v1/stations/{slug}/heartbeat).
 *
 * Built for traffic spikes: the request path only touches Redis plus one
 * small user-row update on the first listen of the day. Ledger writes go to
 * the `points` queue (AwardPoints job).
 *
 * Anti-farming:
 * - A cache lock keyed by *account* (not tab, device or station) admits one
 *   heartbeat per interval, so extra tabs earn nothing extra. On Redis the
 *   lock is atomic across all web servers.
 * - A per-account Redis counter caps listening points per WIB day.
 */
class RecordListeningHeartbeatAction
{
    public const TIMEZONE = 'Asia/Jakarta';

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

        $now = CarbonImmutable::now(self::TIMEZONE);
        $today = $now->startOfDay();
        // Day keys live until shortly after the WIB day ends.
        $dayTtl = (int) $now->diffInSeconds($today->addDay(), true) + 3600;

        $streakBonus = $this->advanceStreak($user, $station, $today, $dayTtl);
        [$listening, $capReached] = $this->awardListening($user, $station, $today, $dayTtl);

        return new HeartbeatResult(
            accepted: true,
            listeningPoints: $listening,
            streakBonus: $streakBonus,
            streakDays: $user->current_streak_days,
            // Includes this heartbeat's points, which the queue is writing now.
            balance: $user->points_balance + $listening + $streakBonus,
            dailyCapReached: $capReached,
            nextInSeconds: $interval,
        );
    }

    /**
     * @return int The streak bonus paid (0 if already counted today).
     */
    private function advanceStreak(User $user, Station $station, CarbonImmutable $today, int $ttl): int
    {
        // Only the first heartbeat of the WIB day continues the streak.
        if (! Cache::add("listened:user:{$user->id}:{$today->toDateString()}", true, $ttl)) {
            return 0;
        }

        $last = $user->last_listened_date;

        // Redis was flushed mid-day: the database still knows today counted.
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
            AwardPoints::dispatch($user, $bonus, PointReason::StreakBonus, $station, [
                'streak_days' => $user->current_streak_days,
            ]);
        }

        return $bonus;
    }

    /**
     * @return array{0: int, 1: bool} Points awarded, and whether today's cap is now reached.
     */
    private function awardListening(User $user, Station $station, CarbonImmutable $today, int $ttl): array
    {
        $cap = (int) config('oz.points.daily_listening_cap');
        $per = (int) config('oz.points.per_heartbeat');
        $key = "listening:user:{$user->id}:{$today->toDateString()}";

        Cache::add($key, 0, $ttl);
        $total = (int) Cache::increment($key, $per);

        // Only the part of this minute that still fits under the cap.
        $points = max(0, min($per, $cap - ($total - $per)));

        if ($points > 0) {
            AwardPoints::dispatch($user, $points, PointReason::Listening, $station);
        }

        return [$points, $total >= $cap];
    }
}
