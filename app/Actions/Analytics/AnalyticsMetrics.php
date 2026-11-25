<?php

namespace App\Actions\Analytics;

use App\Enums\AudienceMode;
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

    /**
     * How articles were read over the window: reads, average active reading
     * time, and the share of reads that got past each quarter of the text.
     *
     * @return array{reads: int, average_seconds: int, reached_25: float|null, reached_50: float|null, reached_75: float|null, finished: float|null}
     */
    public function readingSummary(int $days): array
    {
        return $this->remember("news:reading:{$days}", function () use ($days): array {
            $totals = DB::table('post_reads')
                ->whereBetween('date', $this->lastDates($days))
                ->selectRaw('SUM(reads) as reads, SUM(seconds) as seconds, SUM(reached_25) as r25, SUM(reached_50) as r50, SUM(reached_75) as r75, SUM(finished) as finished')
                ->first();

            $reads = (int) ($totals->reads ?? 0);
            $share = fn (mixed $count): ?float => $reads > 0 ? round((int) $count / $reads * 100, 1) : null;

            return [
                'reads' => $reads,
                'average_seconds' => $reads > 0 ? intdiv((int) $totals->seconds, $reads) : 0,
                'reached_25' => $share($totals->r25 ?? 0),
                'reached_50' => $share($totals->r50 ?? 0),
                'reached_75' => $share($totals->r75 ?? 0),
                'finished' => $share($totals->finished ?? 0),
            ];
        });
    }

    /**
     * Per article over the window: views, reads, average reading time and the
     * share who finished it, most read first.
     *
     * @return list<array{title: string, station: string|null, views: int, reads: int, average_seconds: int, finished: float|null}>
     */
    public function articleReading(int $days): array
    {
        return $this->remember("news:article-reading:{$days}", function () use ($days): array {
            $window = $this->lastDates($days);
            $reads = DB::table('post_reads')
                ->whereBetween('date', $window)
                ->groupBy('post_id')
                ->select('post_id', DB::raw('SUM(reads) as reads'), DB::raw('SUM(seconds) as seconds'), DB::raw('SUM(finished) as finished'))
                ->get()
                ->keyBy('post_id');

            if ($reads->isEmpty()) {
                return [];
            }

            $views = DB::table('post_views')
                ->whereBetween('date', $window)
                ->whereIn('post_id', $reads->keys())
                ->groupBy('post_id')
                ->pluck(DB::raw('SUM(views)'), 'post_id');

            return array_values(Post::query()
                ->with('station')
                ->whereKey($reads->keys())
                ->get()
                ->map(function (Post $post) use ($reads, $views): array {
                    $row = $reads[$post->id];
                    $count = (int) $row->reads;

                    return [
                        'title' => $post->title,
                        'station' => $post->station?->name,
                        'views' => (int) ($views[$post->id] ?? 0),
                        'reads' => $count,
                        'average_seconds' => $count > 0 ? intdiv((int) $row->seconds, $count) : 0,
                        'finished' => $count > 0 ? round((int) $row->finished / $count * 100, 1) : null,
                    ];
                })
                ->sortByDesc('reads')
                ->all());
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

    /* ------------------------------------------- Website audience (player pings) */

    /** Hours listened (audio) or watched (video) on the site, guests included. */
    public function audienceHours(AudienceMode $mode, int $days): float
    {
        return $this->remember("audience-pings:hours:{$mode->value}:{$days}", fn (): float => round((int) DB::table('audience_minutes')
            ->where('mode', $mode->value)
            ->whereBetween('minute_at', $this->lastDays($days))
            ->sum('listeners') / 60, 1));
    }

    /** Different people (devices) who listened or watched (null: either), guests included. */
    public function uniqueAudience(?AudienceMode $mode, int $days): int
    {
        $key = $mode->value ?? 'any';

        return $this->remember("audience-pings:unique:{$key}:{$days}", fn (): int => DB::table('audience_visitors')
            ->when($mode, fn ($query, AudienceMode $mode) => $query->where('mode', $mode->value))
            ->whereBetween('date', $this->lastDates($days))
            ->distinct()
            ->count('visitor'));
    }

    /** Most people listening or watching at once, all stations together. */
    public function peakConcurrent(AudienceMode $mode, int $days): int
    {
        return $this->remember("audience-pings:peak:{$mode->value}:{$days}", fn (): int => (int) DB::query()
            ->fromSub(DB::table('audience_minutes')
                ->where('mode', $mode->value)
                ->whereBetween('minute_at', $this->lastDays($days))
                ->groupBy('minute_at')
                ->select(DB::raw('SUM(listeners) as total')), 'minutes')
            ->max('total'));
    }

    /**
     * Hours and unique people per day, for radio and video.
     *
     * @return list<array{audio_hours: float, video_hours: float, audio_people: int, video_people: int}>
     */
    public function playerSeries(int $days = 14): array
    {
        return $this->remember("audience-pings:series:{$days}", fn (): array => $this->eachDay($days, function (array $range, CarbonImmutable $day): array {
            $hours = fn (AudienceMode $mode): float => round((int) DB::table('audience_minutes')
                ->where('mode', $mode->value)
                ->whereBetween('minute_at', $range)
                ->sum('listeners') / 60, 1);
            $people = fn (AudienceMode $mode): int => DB::table('audience_visitors')
                ->where('mode', $mode->value)
                ->where('date', $day->toDateString())
                ->count();

            return [
                'audio_hours' => $hours(AudienceMode::Audio),
                'video_hours' => $hours(AudienceMode::Video),
                'audio_people' => $people(AudienceMode::Audio),
                'video_people' => $people(AudienceMode::Video),
            ];
        }));
    }

    /**
     * Average listeners by WIB hour of day over the last N days: Icecast (from
     * the 15-minute samples) and the site's radio players (from the pings).
     *
     * @return array{icecast: list<float|null>, website: list<float>}
     */
    public function listenersByHour(int $days = 7): array
    {
        return $this->remember("audience-pings:by-hour:{$days}", function () use ($days): array {
            $range = $this->lastDays($days);
            $hourOf = fn (mixed $at): int => (int) CarbonImmutable::parse((string) $at, config('app.timezone'))
                ->setTimezone(DashboardMetrics::TIMEZONE)
                ->format('G');

            // Icecast: all stations summed per sample time, then averaged per hour.
            $icecast = array_fill(0, 24, []);

            foreach (DB::table('icecast_samples')->whereBetween('sampled_at', $range)->groupBy('sampled_at')
                ->select('sampled_at', DB::raw('SUM(listeners) as total'))->get() as $sample) {
                $icecast[$hourOf($sample->sampled_at)][] = (int) $sample->total;
            }

            // Website: listener-minutes per hour over the minutes in that hour.
            $website = array_fill(0, 24, 0);

            foreach (DB::table('audience_minutes')->where('mode', AudienceMode::Audio->value)->whereBetween('minute_at', $range)
                ->groupBy('minute_at')->select('minute_at', DB::raw('SUM(listeners) as total'))->get() as $minute) {
                $website[$hourOf($minute->minute_at)] += (int) $minute->total;
            }

            return [
                'icecast' => array_map(fn (array $totals): ?float => $totals === [] ? null : round(array_sum($totals) / count($totals), 1), $icecast),
                'website' => array_map(fn (int $minutes): float => round($minutes / ($days * 60), 1), $website),
            ];
        });
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
     * The same days as dayLabels(), as ISO dates ("2026-11-24"), for exports.
     *
     * @return list<string>
     */
    public function dates(int $days): array
    {
        return array_map(fn (CarbonImmutable $day): string => $day->toDateString(), $this->days($days));
    }

    /**
     * Every song aired in the window, most played first (for exports).
     *
     * @return list<array{title: string, artist: string|null, plays: int, average_listeners: float|null}>
     */
    public function topSongs(int $days): array
    {
        return array_values(TrackPlay::query()
            ->whereBetween('started_at', $this->lastDays($days))
            ->groupBy('title', 'artist')
            ->select(['title', 'artist', DB::raw('COUNT(*) as plays'), DB::raw('AVG(listeners) as average_listeners')])
            ->orderByDesc('plays')
            ->toBase()
            ->get()
            ->map(fn (object $row): array => [
                'title' => (string) $row->title,
                'artist' => $row->artist !== null ? (string) $row->artist : null,
                'plays' => (int) $row->plays,
                'average_listeners' => $row->average_listeners !== null ? round((float) $row->average_listeners, 1) : null,
            ])
            ->all());
    }

    /**
     * Signed-in listeners by listening minutes in the window (for exports).
     *
     * @return list<array{name: string, email: string, minutes: int, streak: int, points: int}>
     */
    public function topListeners(int $days): array
    {
        $since = $this->lastDays($days)[0];
        $listening = fn ($query) => $query->where('reason', PointReason::Listening)->where('created_at', '>=', $since);

        return array_values(User::query()
            ->doesntHave('roles')
            ->whereHas('pointTransactions', $listening)
            ->withCount(['pointTransactions as minutes' => $listening])
            ->orderByDesc('minutes')
            ->get()
            ->map(fn (User $user): array => [
                'name' => $user->name,
                'email' => $user->email,
                'minutes' => (int) $user->getAttribute('minutes'),
                'streak' => $user->current_streak_days,
                'points' => $user->points_balance,
            ])
            ->all());
    }

    /**
     * Articles by views in the window (for exports).
     *
     * @return list<array{title: string, station: string|null, section: string|null, published_at: string|null, views: int}>
     */
    public function topArticles(int $days): array
    {
        $inWindow = fn ($query) => $query->whereBetween('date', $this->lastDates($days));

        return array_values(Post::query()
            ->with('station')
            ->whereHas('dailyViews', $inWindow)
            ->withSum(['dailyViews as views' => $inWindow], 'views')
            ->orderByDesc('views')
            ->get()
            ->map(fn (Post $post): array => [
                'title' => $post->title,
                'station' => $post->station?->name,
                'section' => $post->category?->getLabel(),
                'published_at' => $post->published_at?->setTimezone(DashboardMetrics::TIMEZONE)->format('Y-m-d H:i'),
                'views' => (int) $post->getAttribute('views'),
            ])
            ->all());
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
