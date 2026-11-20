<?php

namespace App\Http\Controllers\Public;

use App\Actions\Points\GetLeaderboardAction;
use App\Enums\DayOfWeek;
use App\Http\Controllers\Controller;
use App\Http\Resources\AnnouncerResource;
use App\Http\Resources\EventResource;
use App\Http\Resources\PostResource;
use App\Http\Resources\RedemptionResource;
use App\Http\Resources\RewardResource;
use App\Http\Resources\ScheduleSlotResource;
use App\Http\Resources\SocialHighlightResource;
use App\Models\ProgramSchedule;
use App\Models\Reward;
use App\Models\Station;
use App\Models\User;
use App\Support\PageMeta;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StationController extends Controller
{
    public function home(Request $request, Station $station): Response
    {
        return Inertia::render('station/home', [
            'meta' => PageMeta::make(
                "{$station->name} | {$station->tagline}",
                strip_tags((string) $station->about_content),
            ),
            'news' => PostResource::collection(
                $station->posts()->published()->with('author')->latest('published_at')->limit(3)->get()
            )->resolve($request),
            'events' => EventResource::collection(
                $station->events()->upcoming()->limit(3)->get()
            )->resolve($request),
            'highlights' => SocialHighlightResource::collection(
                $station->socialHighlights()->featured()->ordered()->limit(6)->get()
            )->resolve($request),
        ]);
    }

    public function programs(Request $request, Station $station): Response
    {
        $slots = ProgramSchedule::query()
            ->forStation($station)
            ->whereHas('program', fn (Builder $query) => $query->where('is_active', true))
            ->with(['program.announcers' => fn ($query) => $query->where('is_active', true)])
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return Inertia::render('station/programs', [
            'meta' => PageMeta::make("Programs | {$station->name}", "The weekly {$station->name} schedule, every show and every host."),
            'days' => collect(DayOfWeek::cases())->map(fn (DayOfWeek $day): array => [
                'value' => $day->value,
                'label' => $day->getLabel(),
            ]),
            'slots' => ScheduleSlotResource::collection($slots)->resolve($request),
        ]);
    }

    public function announcers(Request $request, Station $station): Response
    {
        return Inertia::render('station/announcers', [
            'meta' => PageMeta::make("Announcers | {$station->name}", "Meet the voices of {$station->name}."),
            'announcers' => AnnouncerResource::collection(
                $station->announcers()->active()->with(['programs' => fn ($query) => $query->active()])->orderBy('name')->get()
            )->resolve($request),
        ]);
    }

    public function events(Request $request, Station $station): Response
    {
        return Inertia::render('station/events', [
            'meta' => PageMeta::make("Events | {$station->name}", "Shows, meet-ups and gigs from {$station->name} and friends."),
            'upcoming' => EventResource::collection($station->events()->upcoming()->get())->resolve($request),
            'past' => EventResource::collection(
                $station->events()->past()->limit(6)->get()
            )->resolve($request),
        ]);
    }

    /**
     * OZ Poin: how to earn, the leaderboard, rewards and your redemptions.
     */
    public function rewards(Request $request, Station $station, GetLeaderboardAction $leaderboard): Response
    {
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
        return $this->contentPage('station/privacy', "Privacy Policy | {$station->name}", $station->privacy_policy_content);
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
