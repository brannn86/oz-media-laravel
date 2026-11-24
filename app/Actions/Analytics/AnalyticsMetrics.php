<?php

namespace App\Actions\Analytics;

use App\Enums\PointReason;
use App\Enums\PostCategory;
use App\Enums\RedemptionStatus;
use App\Enums\SongRequestStatus;
use App\Models\ChatMessage;
use App\Models\PointTransaction;
use App\Models\Post;
use App\Models\SongRequest;
use App\Models\Station;
use App\Models\TrackPlay;
use App\Models\User;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Figures for the Analytics pages, network-wide (all stations), by WIB day.
 *
 * Everything is cached for five minutes: these pages are for looking back,
 * so a slightly old number is fine and the database is spared during shows.
 * Day series run one indexed count per day (no database-specific date SQL).
 */
class AnalyticsMetrics
{
    private const TTL = 300;

    /* ---------------------------------------------------------------- Audience */

    /** Listener accounts: anyone without an admin role. */
    public function listenerAccounts(): int
    {
        return $this->remember('audience:accounts', fn (): int => User::query()->doesntHave('roles')->count());
    }

    public function newListeners(int $days): int
    {
        return $this->remember("audience:new:{$days}", fn (): int => User::query()
            ->doesntHave('roles')
            ->whereBetween('created_at', $this->lastDays($days))
            ->count());
    }

    /** Signed-in listeners who played the radio for at least a minute. */
    public function activeListeners(int $days): int
    {
        return $this->remember("audience:active:{$days}", fn (): int => PointTransaction::query()
            ->where('reason', PointReason::Listening)
            ->whereBetween('created_at', $this->lastDays($days))
            ->distinct()
            ->count('user_id'));
    }

    public function googleListeners(): int
    {
        return $this->remember('audience:google', fn (): int => User::query()
            ->doesntHave('roles')
            ->whereNotNull('google_id')
            ->count());
    }

    /** Listeners with a listening streak running today or yesterday. */
    public function listenersOnStreak(): int
    {
        return $this->remember('audience:streak', fn (): int => User::query()
            ->where('current_streak_days', '>', 0)
            ->whereDate('last_listened_date', '>=', $this->today()->subDay()->toDateString())
            ->count());
    }

    /**
     * @return list<array{signups: int, active: int}>
     */
    public function audienceSeries(int $days = 30): array
    {
        return $this->remember("audience:series:{$days}", fn (): array => $this->eachDay($days, fn (array $range): array => [
            'signups' => User::query()->doesntHave('roles')->whereBetween('created_at', $range)->count(),
            'active' => PointTransaction::query()
                ->where('reason', PointReason::Listening)
                ->whereBetween('created_at', $range)
                ->distinct()
                ->count('user_id'),
        ]));
    }

    /* ------------------------------------------------------------------- Radio */

    /** Minutes of playback by signed-in listeners (one heartbeat = one minute). */
    public function listeningMinutes(int $days): int
    {
        return $this->remember("radio:minutes:{$days}", fn (): int => PointTransaction::query()
            ->where('reason', PointReason::Listening)
            ->whereBetween('created_at', $this->lastDays($days))
            ->count());
    }

    /**
     * Icecast listeners per day, from the count recorded at the start of
     * every song: the day's average and highest.
     *
     * @return list<array{average: float, highest: int}>
     */
    public function icecastSeries(int $days = 14): array
    {
        return $this->remember("radio:icecast:{$days}", fn (): array => $this->eachDay($days, function (array $range): array {
            $plays = TrackPlay::query()->whereBetween('started_at', $range)->whereNotNull('listeners');

            return [
                'average' => round((float) (clone $plays)->avg('listeners'), 1),
                'highest' => (int) (clone $plays)->max('listeners'),
            ];
        }));
    }

    /**
     * Peak website listeners (presence) per day, all stations.
     *
     * @return list<array{peak: int}>
     */
    public function websitePeakSeries(int $days = 14): array
    {
        return $this->remember("radio:website-peak:{$days}", fn (): array => $this->eachDay($days, fn (array $range, CarbonImmutable $day): array => [
            'peak' => (int) DB::table('station_daily_stats')->where('date', $day->toDateString())->sum('peak_listeners'),
        ]));
    }

    public function songsPlayed(int $days): int
    {
        return $this->remember("radio:songs:{$days}", fn (): int => TrackPlay::query()
            ->whereBetween('started_at', $this->lastDays($days))
            ->count());
    }

    /**
     * @return array{submitted: int, played: int}
     */
    public function songRequests(int $days): array
    {
        return $this->remember("radio:requests:{$days}", fn (): array => [
            'submitted' => SongRequest::query()->whereBetween('created_at', $this->lastDays($days))->count(),
            'played' => SongRequest::query()
                ->where('status', SongRequestStatus::Played)
                ->whereBetween('created_at', $this->lastDays($days))
                ->count(),
        ]);
    }

    /* -------------------------------------------------------------------- News */

    public function articleViews(int $days): int
    {
        return $this->remember("news:views:{$days}", fn (): int => (int) DB::table('post_views')
            ->whereBetween('date', $this->lastDates($days))
            ->sum('views'));
    }

    public function articlesPublished(int $days): int
    {
        return $this->remember("news:published:{$days}", fn (): int => Post::query()
            ->published()
            ->whereBetween('published_at', $this->lastDays($days))
            ->count());
    }

    /**
     * @return list<array{views: int}>
     */
    public function articleViewSeries(int $days = 30): array
    {
        return $this->remember("news:series:{$days}", fn (): array => $this->eachDay($days, fn (array $range, CarbonImmutable $day): array => [
            'views' => (int) DB::table('post_views')->where('date', $day->toDateString())->sum('views'),
        ]));
    }

    /**
     * Views per section over the last N days, most read first.
     *
     * @return array<string, int>
     */
    public function viewsByCategory(int $days = 30): array
    {
        return $this->remember("news:categories:{$days}", function () use ($days): array {
            $rows = DB::table('post_views')
                ->join('posts', 'posts.id', '=', 'post_views.post_id')
                ->whereBetween('post_views.date', $this->lastDates($days))
                ->groupBy('posts.category')
                ->select('posts.category', DB::raw('SUM(post_views.views) as views'))
                ->get();

            $views = [];

            foreach ($rows as $row) {
                $label = PostCategory::tryFrom((string) $row->category)?->getLabel() ?? 'No section';
                $views[$label] = ($views[$label] ?? 0) + (int) $row->views;
            }

            arsort($views);

            return $views;
        });
    }

    /* -------------------------------------------------------------- Engagement */

    public function chatMessages(int $days): int
    {
        return $this->remember("engagement:chat:{$days}", fn (): int => ChatMessage::withTrashed()
            ->whereBetween('created_at', $this->lastDays($days))
            ->count()
            + DB::table('chat_message_archives')->whereBetween('created_at', $this->lastDays($days))->count());
    }

    public function pollVotes(int $days): int
    {
        return $this->remember("engagement:votes:{$days}", fn (): int => DB::table('poll_votes')
            ->whereBetween('created_at', $this->lastDays($days))
            ->count());
    }

    /**
     * @return array{earned: int, spent: int}
     */
    public function points(int $days): array
    {
        return $this->remember("engagement:points:{$days}", fn (): array => [
            'earned' => (int) PointTransaction::query()
                ->whereIn('reason', PointReason::earningValues())
                ->whereBetween('created_at', $this->lastDays($days))
                ->sum('points'),
            'spent' => (int) DB::table('reward_redemptions')
                ->whereBetween('created_at', $this->lastDays($days))
                ->sum('points_spent'),
        ]);
    }

    public function pendingRedemptions(): int
    {
        return $this->remember('engagement:redemptions', fn (): int => DB::table('reward_redemptions')
            ->where('status', RedemptionStatus::Pending->value)
            ->count());
    }

    /**
     * @return list<array{chat: int, requests: int, votes: int}>
     */
    public function engagementSeries(int $days = 14): array
    {
        return $this->remember("engagement:series:{$days}", fn (): array => $this->eachDay($days, fn (array $range): array => [
            'chat' => ChatMessage::withTrashed()->whereBetween('created_at', $range)->count()
                + DB::table('chat_message_archives')->whereBetween('created_at', $range)->count(),
            'requests' => SongRequest::query()->whereBetween('created_at', $range)->count(),
            'votes' => DB::table('poll_votes')->whereBetween('created_at', $range)->count(),
        ]));
    }

    /* ------------------------------------------------------------------- Video */

    public function stationsWithVideo(): int
    {
        return $this->remember('video:stations', fn (): int => Station::query()
            ->active()
            ->whereNotNull('stream_video_url')
            ->where('stream_video_url', '!=', '')
            ->count());
    }

    public function reactions(int $days): int
    {
        return $this->remember("video:reactions:{$days}", fn (): int => (int) DB::table('reaction_counts')
            ->whereBetween('date', $this->lastDates($days))
            ->sum('count'));
    }

    /**
     * Live reactions per day, by type.
     *
     * @return list<array<string, int>>
     */
    public function reactionSeries(int $days = 14): array
    {
        return $this->remember("video:reactions:series:{$days}", fn (): array => $this->eachDay($days, fn (array $range, CarbonImmutable $day): array => DB::table('reaction_counts')
            ->where('date', $day->toDateString())
            ->groupBy('type')
            ->select('type', DB::raw('SUM(count) as total'))
            ->pluck('total', 'type')
            ->map(fn (mixed $total): int => (int) $total)
            ->all()));
    }

    /* ----------------------------------------------------------------- Helpers */

    public function today(): CarbonImmutable
    {
        return CarbonImmutable::now(DashboardMetrics::TIMEZONE)->startOfDay();
    }

    /**
     * From the start of the WIB day N-1 days ago until now, in app time.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function lastDays(int $days): array
    {
        $start = $this->today()->subDays($days - 1)->setTimezone(config('app.timezone'));

        return [$start, CarbonImmutable::now()];
    }

    /**
     * The same window as dates, for tables keyed by WIB day.
     *
     * @return array{0: string, 1: string}
     */
    private function lastDates(int $days): array
    {
        return [$this->today()->subDays($days - 1)->toDateString(), $this->today()->toDateString()];
    }

    /**
     * Chart labels for the last N WIB days ("24 Nov"), oldest first; they line
     * up with every *Series() method.
     *
     * @return list<string>
     */
    public function dayLabels(int $days): array
    {
        return array_map(fn (CarbonImmutable $day): string => $day->format('d M'), $this->days($days));
    }

    /**
     * @return list<CarbonImmutable>
     */
    private function days(int $days): array
    {
        return array_map(fn (int $ago): CarbonImmutable => $this->today()->subDays($ago), range($days - 1, 0));
    }

    /**
     * One row per WIB day, oldest first.
     *
     * @template T
     *
     * @param  Closure(array{0: CarbonImmutable, 1: CarbonImmutable}, CarbonImmutable): T  $row
     * @return list<T>
     */
    private function eachDay(int $days, Closure $row): array
    {
        return array_map(function (CarbonImmutable $day) use ($row): mixed {
            $start = $day->setTimezone(config('app.timezone'));

            return $row([$start, $start->addDay()->subSecond()], $day);
        }, $this->days($days));
    }

    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    private function remember(string $key, Closure $callback): mixed
    {
        return Cache::remember("analytics:{$key}", self::TTL, $callback);
    }
}
