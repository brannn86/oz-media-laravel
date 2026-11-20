<?php

namespace App\Filament\Pages;

use App\Actions\Broadcast\BroadcastStatus;
use App\Actions\Broadcast\GetCurrentBroadcastAction;
use App\Actions\Broadcast\ListenerPresence;
use App\Actions\Chat\ModerateChatAction;
use App\Filament\Actions\ToggleStreamModeAction;
use App\Filament\NavigationGroup;
use App\Models\ChatMessage;
use App\Models\Station;
use App\Models\User;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * Live studio monitor: listener count, the chat feed with one-click
 * moderation, and quick switches for video live and slow mode.
 *
 * Updates arrive over Reverb (Livewire Echo listeners on the chat channel);
 * the listener count is polled from the presence channel every few seconds.
 *
 * @property-read Collection<int, ChatMessage> $messages
 * @property-read ChatMessage|null $pinned
 * @property-read int|null $listenerCount
 * @property-read BroadcastStatus $broadcast
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
        ];
    }

    public function refreshFeed(): void
    {
        unset($this->messages, $this->pinned);
        $this->station->refresh();
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
            ToggleStreamModeAction::make($this->station),
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
