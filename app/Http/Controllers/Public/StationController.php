<?php

namespace App\Http\Controllers\Public;

use App\Actions\Points\GetLeaderboardAction;
use App\Enums\DayOfWeek;
use App\Http\Controllers\Controller;
use App\Http\Resources\AnnouncerResource;
use App\Http\Resources\EventResource;
use App\Http\Resources\PostResource;
use App\Http\Resources\ProgramResource;
use App\Http\Resources\RedemptionResource;
use App\Http\Resources\RewardResource;
use App\Http\Resources\ScheduleSlotResource;
use App\Http\Resources\SocialHighlightResource;
use App\Models\Program;
use App\Models\ProgramSchedule;
use App\Models\Reward;
use App\Models\Station;
use App\Models\User;
use App\Support\PageMeta;
use App\Support\StationCache;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StationController extends Controller
{
    /** Lists whose contents change with the clock (scheduled posts, upcoming events). */
    private const TIME_SENSITIVE_TTL = 120;

    public function home(Request $request, Station $station): Response
    {
        if ($station->isBasic()) {
            return Inertia::render('basic/home', [
                'meta' => PageMeta::make($station->tagline ? "{$station->name} | {$station->tagline}" : $station->name),
                'sponsor' => $this->sponsor($station),
            ]);
        }

        return Inertia::render('station/home', [
            'sponsor' => $this->sponsor($station),
            'meta' => PageMeta::make(
                "{$station->name} | {$station->tagline}",
                strip_tags((string) $station->about_content),
            ),
            // Short TTL: scheduled posts and events go live by the clock, not by an edit.
            'news' => StationCache::remember($station, 'home-news', fn (): array => PostResource::collection(
                $station->posts()->published()->with('author')->latest('published_at')->limit(3)->get()
            )->resolve($request), ttl: self::TIME_SENSITIVE_TTL),
            'events' => StationCache::remember($station, 'home-events', fn (): array => EventResource::collection(
                $station->events()->upcoming()->limit(3)->get()
            )->resolve($request), ttl: self::TIME_SENSITIVE_TTL),
            'highlights' => StationCache::remember($station, 'home-highlights', fn (): array => SocialHighlightResource::collection(
                $station->socialHighlights()->featured()->ordered()->limit(6)->get()
            )->resolve($request)),
            // Shows in the order of the day (earliest start first), unscheduled ones last.
            'programs' => StationCache::remember($station, 'home-programs', fn (): array => ProgramResource::collection(
                $station->programs()->active()
                    ->with(['schedules', 'announcers' => fn ($query) => $query->where('is_active', true)])
                    ->orderBy('title')
                    ->get()
                    ->sortBy(fn (Program $program): string => $program->schedules->min('start_time') ?? '99:99')
                    ->values()
            )->resolve($request)),
            'announcers' => StationCache::remember($station, 'home-announcers', fn (): array => AnnouncerResource::collection(
                $station->announcers()->active()->with(['programs' => fn ($query) => $query->active()])->orderBy('name')->get()
            )->resolve($request)),
        ]);
    }

    public function programs(Request $request, Station $station): Response
    {
        return Inertia::render('station/programs', [
            'meta' => PageMeta::make("Programs | {$station->name}", "The weekly {$station->name} schedule, every show and every host."),
            'days' => collect(DayOfWeek::cases())->map(fn (DayOfWeek $day): array => [
                'value' => $day->value,
                'label' => $day->getLabel(),
            ]),
            'slots' => StationCache::remember($station, 'programs-page', fn (): array => ScheduleSlotResource::collection(
                ProgramSchedule::query()
                    ->forStation($station)
                    ->whereHas('program', fn (Builder $query) => $query->where('is_active', true))
                    ->with(['program.media', 'program.announcers' => fn ($query) => $query->where('is_active', true)])
                    ->orderBy('day_of_week')
                    ->orderBy('start_time')
                    ->get()
            )->resolve($request)),
        ]);
    }

    public function announcers(Request $request, Station $station): Response
    {
        return Inertia::render('station/announcers', [
            'meta' => PageMeta::make("Announcers | {$station->name}", "Meet the voices of {$station->name}."),
            'announcers' => StationCache::remember($station, 'announcers-page', fn (): array => AnnouncerResource::collection(
                $station->announcers()->active()->with(['programs' => fn ($query) => $query->active()])->orderBy('name')->get()
            )->resolve($request)),
        ]);
    }

    public function events(Request $request, Station $station): Response
    {
        return Inertia::render('station/events', [
            'meta' => PageMeta::make("Events | {$station->name}", "Shows, meet-ups and gigs from {$station->name} and friends."),
            'upcoming' => EventResource::collection($station->events()->with('media')->upcoming()->get())->resolve($request),
            'past' => EventResource::collection(
                $station->events()->with('media')->past()->limit(6)->get()
            )->resolve($request),
        ]);
    }

    /**
     * One event on its own page, with what else is coming up.
     */
    public function event(Request $request, Station $station, string $slug): Response
    {
        $event = $station->events()->where('slug', $slug)->firstOrFail();

        return Inertia::render('station/events/show', [
            'meta' => PageMeta::make(
                "{$event->title} | {$station->name}",
                $event->description ?: "{$event->title}, {$event->start_date->timezone('Asia/Jakarta')->translatedFormat('j F Y')}.",
                $event->webpImageUrl('poster'),
            ),
            'event' => EventResource::make($event)->resolve($request),
            'upcoming' => EventResource::collection(
                $station->events()->upcoming()->whereKeyNot($event->getKey())->limit(3)->get()
            )->resolve($request),
        ]);
    }

    /**
     * OZ Poin: how to earn, the leaderboard, rewards and your redemptions.
     */
    public function rewards(Request $request, Station $station, GetLeaderboardAction $leaderboard): Response
    {
        // Switched off in Station settings: the page goes, the points don't.
        abort_unless($station->rewards_page_enabled, 404);

        $user = $request->user();

        return Inertia::render('station/rewards', [
            'meta' => PageMeta::make("OZ Poin | {$station->name}", "Listen, request songs and vote in polls to earn OZ Poin, then swap them for {$station->name} merch and tickets."),
            'leaderboard' => [
                'week' => $leaderboard->handle($station, 'week'),
                'all' => $leaderboard->handle($station, 'all'),
            ],
            'rewards' => RewardResource::collection(Reward::query()->availableOn($station)->get())->resolve($request),
            'redemptions' => $user instanceof User
                ? RedemptionResource::collection($user->redemptions()->with('reward')->latest()->limit(20)->get())->resolve($request)
                : [],
            'earning' => [
                'per_minute' => (int) config('oz.points.per_heartbeat'),
                'daily_listening_cap' => (int) config('oz.points.daily_listening_cap'),
                'streak_bonus_per_day' => (int) config('oz.points.streak_bonus_per_day'),
                'streak_bonus_max_days' => (int) config('oz.points.streak_bonus_max_days'),
                'song_request_approved' => (int) config('oz.points.song_request_approved'),
                'poll_vote' => (int) config('oz.points.poll_vote'),
            ],
        ]);
    }

    public function about(Station $station): Response
    {
        return $this->contentPage('station/about', "About | {$station->name}", $station->about_content);
    }

    public function privacy(Station $station): Response
    {
        return $this->contentPage($station->isBasic() ? 'basic/privacy' : 'station/privacy', "Privacy Policy | {$station->name}", $station->privacy_policy_content);
    }

    /**
     * The banner under the player: the uploaded one, or the house ad
     * (`house_email` is where advertisers write) when there is none. Null
     * when the ad space is switched off in Station settings.
     *
     * @return array{name: string|null, url: string|null, image_url: string, mobile_image_url: string|null}|array{house_email: string}|null
     */
    private function sponsor(Station $station): ?array
    {
        if (! $station->sponsor_enabled) {
            return null;
        }

        return StationCache::remember($station, 'sponsor', fn (): array => $station->sponsor()
            ?? ['house_email' => (string) config('oz.advertising_email')]);
    }

    private function contentPage(string $component, string $title, ?string $html): Response
    {
        $body = filled($html) ? RichContentRenderer::make($html)->toHtml() : null;

        return Inertia::render($component, [
            'meta' => PageMeta::make($title, $body),
            'title' => $title,
            'body' => $body,
        ]);
    }
}
