<?php

namespace App\Actions\Points;

use App\Enums\PointReason;
use App\Models\PointTransaction;
use App\Models\Station;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

/**
 * Top listeners by OZ Poin *earned* on a station (spending doesn't lower
 * your rank). "week" starts Monday 00:00 WIB.
 */
class GetLeaderboardAction
{
    public const PERIODS = ['week', 'all'];

    private const CACHE_SECONDS = 60;

    /**
     * @return list<array{rank: int, user: array{id: int, name: string, avatar_url: string|null}, points: int}>
     */
    public function handle(Station $station, string $period = 'week', int $limit = 20): array
    {
        $period = in_array($period, self::PERIODS, true) ? $period : 'week';

        return Cache::remember(
            "leaderboard:{$station->id}:{$period}:{$limit}",
            self::CACHE_SECONDS,
            fn (): array => $this->compute($station, $period, $limit),
        );
    }

    /**
     * @return list<array{rank: int, user: array{id: int, name: string, avatar_url: string|null}, points: int}>
     */
    private function compute(Station $station, string $period, int $limit): array
    {
        $rows = PointTransaction::query()
            ->selectRaw('user_id, sum(points) as total')
            ->where('station_id', $station->id)
            ->whereIn('reason', PointReason::earningValues())
            ->when($period === 'week', fn ($query) => $query->where(
                'created_at',
                '>=',
                CarbonImmutable::now('Asia/Jakarta')->startOfWeek()->setTimezone(config('app.timezone')),
            ))
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->orderBy('user_id')
            ->limit($limit)
            ->get();

        $users = User::query()->whereIn('id', $rows->pluck('user_id'))->get(['id', 'name', 'avatar_url'])->keyBy('id');

        $board = [];

        foreach ($rows as $index => $row) {
            $user = $users->get($row->user_id);

            if ($user instanceof User) {
                $board[] = [
                    'rank' => $index + 1,
                    'user' => ['id' => $user->id, 'name' => $user->name, 'avatar_url' => $user->avatar_url],
                    'points' => (int) $row->getAttribute('total'),
                ];
            }
        }

        return $board;
    }
}
