<?php

namespace App\Actions\Analytics;

use App\Actions\NowPlaying\SyncNowPlayingAction;
use App\Enums\SongRequestStatus;
use App\Models\SongRequest;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

/**
 * Song requests against what actually went on air: how many are played,
 * rejected or still waiting, how long played ones waited, and which songs
 * are asked for most. A request counts as played when the stream played it
 * (matched automatically) or a moderator marked it played; its played time
 * is then decided_at.
 */
class RequestMetrics
{
    private const TTL = 900;

    /**
     * @return array{submitted: int, played: int, rejected: int, waiting: int, play_rate: float|null, median_wait_minutes: float|null, average_wait_minutes: float|null}
     */
    public function summary(int $days): array
    {
        return Cache::remember("analytics:requests:summary:{$days}", self::TTL, function () use ($days): array {
            $requests = SongRequest::query()
                ->where('created_at', '>=', $this->since($days))
                ->get(['status', 'created_at', 'decided_at']);

            $count = fn (SongRequestStatus ...$statuses): int => $requests->filter(fn (SongRequest $request): bool => in_array($request->status, $statuses, true))->count();
            $played = $requests->filter(fn (SongRequest $request): bool => $request->status === SongRequestStatus::Played && $request->decided_at !== null);
            $waits = $played
                ->map(fn (SongRequest $request): float => max(0, $request->created_at?->diffInSeconds($request->decided_at) ?? 0) / 60)
                ->sort()
                ->values();
            $decided = $count(SongRequestStatus::Played, SongRequestStatus::Rejected);

            return [
                'submitted' => $requests->count(),
                'played' => $played->count(),
                'rejected' => $count(SongRequestStatus::Rejected),
                'waiting' => $count(SongRequestStatus::Pending, SongRequestStatus::Approved),
                // Of the requests that reached a decision (played or rejected).
                'play_rate' => $decided > 0 ? round($played->count() / $decided * 100, 1) : null,
                'median_wait_minutes' => $waits->isEmpty() ? null : round((float) $waits->median(), 1),
                'average_wait_minutes' => $waits->isEmpty() ? null : round((float) $waits->avg(), 1),
            ];
        });
    }

    /**
     * Songs asked for most (same song however it was typed), with how many
     * of those requests were played.
     *
     * @return list<array{song: string, artist: string, requests: int, people: int, upvotes: int, played: int}>
     */
    public function mostRequested(int $days, int $limit = 20): array
    {
        return Cache::remember("analytics:requests:songs:{$days}:{$limit}", self::TTL, fn (): array => array_values(SongRequest::query()
            ->where('created_at', '>=', $this->since($days))
            ->get(['song_title', 'artist', 'user_id', 'upvotes_count', 'status'])
            ->groupBy(fn (SongRequest $request): string => SyncNowPlayingAction::normalize($request->song_title).'|'.SyncNowPlayingAction::normalize($request->artist))
            ->map(fn ($group): array => [
                'song' => (string) $group->first()->song_title,
                'artist' => (string) $group->first()->artist,
                'requests' => $group->count(),
                'people' => $group->pluck('user_id')->unique()->count(),
                'upvotes' => (int) $group->sum('upvotes_count'),
                'played' => $group->filter(fn (SongRequest $request): bool => $request->status === SongRequestStatus::Played)->count(),
            ])
            ->sortByDesc(fn (array $row): int => $row['requests'] * 1000 + $row['upvotes'])
            ->take($limit)
            ->all()));
    }

    /**
     * Requests made and played per WIB day.
     *
     * @return list<array{requested: int, played: int}>
     */
    public function daily(int $days): array
    {
        return Cache::remember("analytics:requests:daily:{$days}", self::TTL, function () use ($days): array {
            $rows = [];

            for ($ago = $days - 1; $ago >= 0; $ago--) {
                $start = CarbonImmutable::now(DashboardMetrics::TIMEZONE)->startOfDay()->subDays($ago)->setTimezone(config('app.timezone'));
                $range = [$start, $start->addDay()->subSecond()];

                $rows[] = [
                    'requested' => SongRequest::query()->whereBetween('created_at', $range)->count(),
                    'played' => SongRequest::query()->where('status', SongRequestStatus::Played)->whereBetween('decided_at', $range)->count(),
                ];
            }

            return $rows;
        });
    }

    private function since(int $days): CarbonImmutable
    {
        return CarbonImmutable::now(DashboardMetrics::TIMEZONE)->startOfDay()->subDays($days - 1)->setTimezone(config('app.timezone'));
    }
}
