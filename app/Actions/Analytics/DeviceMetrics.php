<?php

namespace App\Actions\Analytics;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * What listeners use: from the site's and app's sessions (device, platform,
 * OS, browser), and from Icecast's own listener list (every player, when an
 * Icecast admin login is configured).
 */
class DeviceMetrics
{
    private const TTL = 900;

    /** Session columns that can be broken down. */
    public const DIMENSIONS = [
        'device' => 'Device',
        'platform' => 'Website or app',
        'os' => 'Operating system',
        'browser' => 'Browser',
    ];

    /**
     * Sessions and people per value of $dimension, most sessions first.
     *
     * @return list<array{label: string, sessions: int, people: int}>
     */
    public function breakdown(string $dimension, int $days): array
    {
        if (! array_key_exists($dimension, self::DIMENSIONS)) {
            return [];
        }

        return Cache::remember("analytics:devices:{$dimension}:{$days}", self::TTL, fn (): array => array_values(DB::table('audience_sessions')
            ->where('started_at', '>=', $this->since($days))
            ->groupBy($dimension)
            ->select([
                "{$dimension} as label",
                DB::raw('COUNT(*) as sessions'),
                DB::raw('COUNT(DISTINCT visitor) as people'),
            ])
            ->orderByDesc('sessions')
            ->get()
            ->map(fn (object $row): array => [
                'label' => $row->label !== null ? (string) $row->label : 'Unknown',
                'sessions' => (int) $row->sessions,
                'people' => (int) $row->people,
            ])
            ->all()));
    }

    /**
     * Icecast listeners by kind of player: the average at the 15-minute
     * samples over the window, all stations together. Empty without an
     * Icecast admin login (nothing is sampled).
     *
     * @return list<array{category: string, average: float, highest: int}>
     */
    public function icecastPlayers(int $days): array
    {
        return Cache::remember("analytics:icecast-players:{$days}", self::TTL, function () use ($days): array {
            $rows = DB::table('icecast_client_samples')
                ->where('sampled_at', '>=', $this->since($days))
                ->groupBy('sampled_at', 'category')
                ->select('sampled_at', 'category', DB::raw('SUM(listeners) as listeners'))
                ->get();

            $samples = $rows->pluck('sampled_at')->unique()->count();

            return array_values($rows->groupBy('category')
                ->map(fn ($group, $category): array => [
                    'category' => (string) $category,
                    'average' => round($group->sum('listeners') / max(1, $samples), 1),
                    'highest' => (int) $group->max('listeners'),
                ])
                ->sortByDesc('average')
                ->all());
        });
    }

    private function since(int $days): CarbonImmutable
    {
        return CarbonImmutable::now(DashboardMetrics::TIMEZONE)->startOfDay()->subDays($days - 1)->setTimezone(config('app.timezone'));
    }
}
