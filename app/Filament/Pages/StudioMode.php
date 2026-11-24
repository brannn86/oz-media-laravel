<?php

namespace App\Filament\Pages;

use App\Actions\Broadcast\BroadcastStatus;
use App\Actions\Broadcast\GetCurrentBroadcastAction;
use App\Actions\Broadcast\ListenerPresence;
use App\Actions\Chat\ModerateChatAction;
use App\Actions\Polls\ClosePollAction;
use App\Actions\Polls\LaunchPollAction;
use App\Actions\SongRequests\ModerateSongRequestAction;
use App\Enums\PollStatus;
use App\Enums\PollType;
use App\Filament\NavigationGroup;
use App\Models\ChatMessage;
use App\Models\Poll;
use App\Models\SongRequest;
use App\Models\Station;
use App\Models\User;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * Live studio monitor: listener count, the chat feed with one-click
 * moderation, the song request queue, live polls, floating reactions, and
 * quick switches for video live and slow mode.
 *
 * Updates arrive over Reverb (Livewire Echo listeners on the chat, request
 * and poll channels; reactions are drawn client-side by Alpine). The listener
 * count is polled from the presence channel every few seconds.
 *
 * @property-read Collection<int, ChatMessage> $messages
 * @property-read ChatMessage|null $pinned
 * @property-read int|null $listenerCount
 * @property-read BroadcastStatus $broadcast
 * @property-read Collection<int, SongRequest> $songRequests
 * @property-read Poll|null $poll
 */
class StudioMode extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.studio-mode';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::LiveChat;

    protected static ?string $navigationLabel = 'Studio Mode';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'studio-mode';

    protected const FEED_SIZE = 100;

    #[Url(as: 'station')]
    public ?string $stationSlug = null;

    #[Locked]
    public Station $station;

    /** How long the one-click Mute button silences someone. */
    public int $muteMinutes = 10;

    public function mount(): void
    {
        $this->station = Station::query()
            ->when($this->stationSlug, fn ($query, string $slug) => $query->where('slug', $slug))
            ->orderBy('id')
            ->firstOrFail();

        $this->stationSlug = $this->station->slug;
        $this->muteMinutes = (int) (config('oz.chat.mute_options_minutes')[0] ?? 10);
    }

    public function getTitle(): string|Htmlable
    {
        return isset($this->station) ? "Studio Mode: {$this->station->name}" : 'Studio Mode';
    }

    /**
     * Re-render whenever anything happens in this station's chat.
     *
     * @return array<string, string>
     */
    public function getListeners(): array
    {
        if (! isset($this->station)) {
            return [];
        }

        $channel = $this->station->chatChannel();

        return [
            "echo:{$channel},.chat.message.sent" => 'refreshFeed',
            "echo:{$channel},.chat.message.deleted" => 'refreshFeed',
            "echo:{$channel},.chat.message.pinned" => 'refreshFeed',
            "echo:{$channel},.chat.settings.updated" => 'refreshFeed',
            "echo:{$this->station->requestsChannel()},.song-request.updated" => 'refreshRequests',
            "echo:{$this->station->pollsChannel()},.poll.updated" => 'refreshPoll',
        ];
    }

    public function refreshFeed(): void
    {
        unset($this->messages, $this->pinned);
        $this->station->refresh();
    }

    public function refreshRequests(): void
    {
        unset($this->songRequests);
    }

    public function refreshPoll(): void
    {
        unset($this->poll);
    }

    public function canHost(): bool
    {
        return (bool) auth()->user()?->can('Host:StudioMode');
    }

    /**
     * The live request queue: coming up first, then most upvoted.
     *
     * @return Collection<int, SongRequest>
     */
    #[Computed]
    public function songRequests(): Collection
    {
        return $this->station->songRequests()->queue()->with('user')->limit(30)->get();
    }

    /**
     * The live poll, or the one that just closed (results stay up for 10 minutes).
     */
    #[Computed]
    public function poll(): ?Poll
    {
        return $this->station->polls()
            ->where(fn ($query) => $query
                ->where('status', PollStatus::Live)
                ->orWhere('closed_at', '>=', now()->subMinutes(10)))
            ->with('options')
            ->orderByRaw('case when status = ? then 0 else 1 end', [PollStatus::Live->value])
            ->latest('opened_at')
            ->first();
    }

    public function approveRequestAction(): Action
    {
        return $this->requestAction('approveRequest', 'Coming up', Heroicon::OutlinedCheck, 'success',
            fn (SongRequest $request, ModerateSongRequestAction $moderate) => $moderate->approve($request));
    }

    public function playRequestAction(): Action
    {
        return $this->requestAction('playRequest', 'Played', Heroicon::OutlinedPlay, 'info',
            fn (SongRequest $request, ModerateSongRequestAction $moderate) => $moderate->markPlayed($request));
    }

    public function rejectRequestAction(): Action
    {
        return $this->requestAction('rejectRequest', 'Reject', Heroicon::OutlinedXMark, 'danger',
            fn (SongRequest $request, ModerateSongRequestAction $moderate) => $moderate->reject($request));
    }

    public function launchPollAction(): Action
    {
        return Action::make('launchPoll')
            ->label('Launch a poll')
            ->icon(Heroicon::OutlinedChartBar)
            ->authorize(fn (): bool => $this->canHost())
            ->modalHeading('Launch a live poll')
            ->modalDescription('It pops up on every listener\'s player straight away. Launching replaces any poll that is still open.')
            ->modalSubmitActionLabel('Go live')
            ->schema([
                ToggleButtons::make('type')
                    ->options(PollType::class)
                    ->default(PollType::SongBattle->value)
                    ->inline()
                    ->live()
                    ->required(),
                TextInput::make('question')
                    ->required()
                    ->maxLength(200)
                    ->placeholder('Song battle: which one should we play next?'),
                Repeater::make('options')
                    ->label('Answers')
                    ->simple(TextInput::make('label')->required()->maxLength(120))
                    ->minItems(2)
                    ->maxItems(4)
                    // A simple repeater holds plain strings, one per answer.
                    ->default(['', ''])
                    ->addActionLabel('Add answer')
                    ->reorderable(false),
                Select::make('correct')
                    ->label('Correct answer')
                    ->helperText('Revealed to listeners when you close the poll.')
                    ->options(fn (Get $get): array => array_filter(array_map(
                        fn (array $option): string => $option['label'],
                        $this->pollOptions($get('options')),
                    )))
                    ->visible(fn (Get $get): bool => $this->pollType($get('type')) === PollType::Trivia)
                    ->required(fn (Get $get): bool => $this->pollType($get('type')) === PollType::Trivia),
            ])
            ->action(function (array $data, LaunchPollAction $launch): void {
                $type = $this->pollType($data['type']) ?? PollType::General;
                $correct = $type === PollType::Trivia && is_numeric($data['correct'] ?? null) ? (int) $data['correct'] : null;

                $launch->handle(
                    $this->station,
                    auth()->user(),
                    is_string($data['question'] ?? null) ? $data['question'] : '',
                    $type,
                    $this->pollOptions($data['options'] ?? [], $correct),
                );
                $this->refreshPoll();

                Notification::make()->title('Poll is live')->success()->send();
            });
    }

    public function closePollAction(): Action
    {
        return Action::make('closePoll')
            ->label(fn (): string => $this->poll?->type === PollType::Trivia ? 'Close & reveal answer' : 'Close poll')
            ->icon(Heroicon::OutlinedStopCircle)
            ->color('gray')
            ->authorize(fn (): bool => $this->canHost())
            ->visible(fn (): bool => $this->poll?->isLive() === true)
            ->action(function (ClosePollAction $close): void {
                if ($this->poll) {
                    $close->handle($this->poll);
                    $this->refreshPoll();
                }
            });
    }

    private function requestAction(string $name, string $label, Heroicon $icon, string $color, Closure $change): Action
    {
        return Action::make($name)
            ->label($label)
            ->icon($icon)
            ->color($color)
            ->size('sm')
            ->link()
            ->authorize(fn (): bool => $this->canHost())
            ->action(function (array $arguments) use ($change): void {
                $request = $this->station->songRequests()->with('user', 'station')->find($arguments['request'] ?? null);

                if (! $request instanceof SongRequest) {
                    return;
                }

                try {
                    app()->call($change, ['request' => $request]);
                } catch (ValidationException $e) {
                    Notification::make()->title(collect($e->errors())->flatten()->first())->warning()->send();
                }

                $this->refreshRequests();
            });
    }

    /**
     * The repeater's answers as LaunchPollAction expects them.
     *
     * @return list<array{label: string, is_correct: bool}>
     */
    private function pollOptions(mixed $raw, ?int $correctIndex = null): array
    {
        $options = [];

        foreach (is_array($raw) ? array_values($raw) : [] as $index => $option) {
            $label = is_array($option) ? ($option['label'] ?? '') : $option;

            $options[] = [
                'label' => is_string($label) ? $label : '',
                'is_correct' => $correctIndex === $index,
            ];
        }

        return $options;
    }

    private function pollType(mixed $value): ?PollType
    {
        return $value instanceof PollType ? $value : PollType::tryFrom((string) $value);
    }

    /**
     * @return Collection<int, ChatMessage>
     */
    #[Computed]
    public function messages(): Collection
    {
        return $this->station->chatMessages()
            ->with('user.roles')
            ->latest('id')
            ->limit(self::FEED_SIZE)
            ->get()
            ->reverse()
            ->values();
    }

    #[Computed]
    public function pinned(): ?ChatMessage
    {
        return $this->station->chatMessages()->pinned()->with('user.roles')->first();
    }

    #[Computed]
    public function listenerCount(): ?int
    {
        return app(ListenerPresence::class)->count($this->station);
    }

    #[Computed]
    public function broadcast(): BroadcastStatus
    {
        return app(GetCurrentBroadcastAction::class)->handle($this->station);
    }

    /**
     * @return list<int>
     */
    public function slowModeOptions(): array
    {
        return config('oz.chat.slow_mode_options');
    }

    /**
     * @return list<int>
     */
    public function muteOptions(): array
    {
        return config('oz.chat.mute_options_minutes');
    }

    public function setSlowMode(int $seconds): void
    {
        Gate::authorize('moderate', ChatMessage::class);

        if (! in_array($seconds, $this->slowModeOptions(), true)) {
            return;
        }

        app(ModerateChatAction::class)->setSlowMode($this->station, $seconds);

        Notification::make()
            ->title($seconds === 0 ? 'Slow mode off' : "Slow mode: one message every {$seconds}s")
            ->success()
            ->send();
    }

    public function setMuteMinutes(int $minutes): void
    {
        if (in_array($minutes, $this->muteOptions(), true)) {
            $this->muteMinutes = $minutes;
        }
    }

    public function pinAction(): Action
    {
        return Action::make('pin')
            ->label(fn (array $arguments): string => $this->findMessage($arguments)?->is_pinned ? 'Unpin' : 'Pin')
            ->icon(Heroicon::OutlinedMapPin)
            ->iconButton()
            ->color('warning')
            ->size('sm')
            ->authorize(fn (): bool => Gate::allows('moderate', ChatMessage::class))
            ->action(function (array $arguments, ModerateChatAction $moderate): void {
                $message = $this->findMessage($arguments);

                if (! $message) {
                    return;
                }

                $message->is_pinned ? $moderate->unpin($message) : $moderate->pin($message);
                $this->refreshFeed();
            });
    }

    public function deleteMessageAction(): Action
    {
        return Action::make('deleteMessage')
            ->label('Delete')
            ->icon(Heroicon::OutlinedTrash)
            ->iconButton()
            ->color('danger')
            ->size('sm')
            ->authorize(fn (): bool => Gate::allows('moderate', ChatMessage::class))
            ->action(function (array $arguments, ModerateChatAction $moderate): void {
                if ($message = $this->findMessage($arguments)) {
                    $moderate->delete($message);
                    $this->refreshFeed();
                }
            });
    }

    public function muteAction(): Action
    {
        return Action::make('mute')
            ->label(fn (array $arguments): string => $this->findMessage($arguments)?->user?->isChatMuted() ? 'Unmute' : "Mute {$this->muteLabel($this->muteMinutes)}")
            ->icon(Heroicon::OutlinedSpeakerXMark)
            ->iconButton()
            ->color('gray')
            ->size('sm')
            ->authorize(fn (): bool => Gate::allows('moderate', ChatMessage::class))
            // Not offered on staff messages (see the view); ModerateChatAction refuses them too.
            ->action(function (array $arguments, ModerateChatAction $moderate): void {
                $user = $this->findMessage($arguments)?->user;

                if (! $user instanceof User) {
                    return;
                }

                try {
                    if ($user->isChatMuted()) {
                        $moderate->unmute($user);
                        Notification::make()->title("{$user->name} can chat again")->success()->send();
                    } else {
                        $moderate->mute($user, $this->muteMinutes);
                        Notification::make()->title("{$user->name} muted for {$this->muteLabel($this->muteMinutes)}")->success()->send();
                    }
                } catch (InvalidArgumentException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();
                }

                unset($this->messages, $this->pinned);
            });
    }

    public function muteLabel(int $minutes): string
    {
        return match (true) {
            $minutes >= 1440 && $minutes % 1440 === 0 => ($minutes / 1440).' d',
            $minutes >= 60 && $minutes % 60 === 0 => ($minutes / 60).' h',
            default => "{$minutes} min",
        };
    }

    protected function getHeaderActions(): array
    {
        $others = Station::query()->whereKeyNot($this->station->getKey())->orderBy('name')->get();

        return [
            $this->launchPollAction(),
            ...($others->isEmpty() ? [] : [
                ActionGroup::make($others->map(fn (Station $station): Action => Action::make("switch-{$station->id}")
                    ->label($station->name)
                    ->url(static::getUrl(['station' => $station->slug])))->all())
                    ->label('Switch station')
                    ->icon(Heroicon::OutlinedArrowsRightLeft)
                    ->button()
                    ->color('gray'),
            ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    private function findMessage(array $arguments): ?ChatMessage
    {
        $id = $arguments['message'] ?? null;

        if (! is_numeric($id)) {
            return null;
        }

        // Labels and visibility run per message per render: use the loaded feed,
        // and only query for something that has scrolled out of it.
        return $this->messages->firstWhere('id', (int) $id)
            ?? ($this->pinned?->id === (int) $id ? $this->pinned : null)
            ?? $this->station->chatMessages()->with('user.roles')->find((int) $id);
    }
}
