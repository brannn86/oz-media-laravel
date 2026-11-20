<?php

namespace App\Filament\Pages;

use App\Actions\Chat\ModerateChatAction;
use App\Enums\AdminRole;
use App\Filament\NavigationGroup;
use App\Models\ChatMessage;
use App\Models\User;
use BackedEnum;
use Carbon\CarbonInterface;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

/**
 * Everyone currently muted in live chat, with unmute and change-length,
 * plus muting someone by name without waiting for them to post.
 *
 * Access follows the chat moderation permission (all three admin roles).
 */
class MutedListeners extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSpeakerXMark;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::LiveChat;

    protected static ?string $navigationLabel = 'Muted Listeners';

    protected static ?string $title = 'Muted listeners';

    protected static ?string $slug = 'chat/muted-listeners';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return Gate::allows('moderate', ChatMessage::class);
    }

    public static function getNavigationBadge(): ?string
    {
        $count = User::query()->where('chat_muted_until', '>', now())->count();

        return $count > 0 ? (string) $count : null;
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(User::query()->where('chat_muted_until', '>', now()))
            ->defaultSort('chat_muted_until')
            ->poll('30s')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->weight('medium')
                    ->description(fn (User $record): string => $record->email),
                TextColumn::make('chat_muted_until')
                    ->label('Muted until (WIB)')
                    ->dateTime('D d M, H:i', timezone: 'Asia/Jakarta')
                    ->description(fn (User $record): ?string => $record->chat_muted_until
                        ? $record->chat_muted_until->diffForHumans(syntax: CarbonInterface::DIFF_ABSOLUTE, parts: 2).' left'
                        : null)
                    ->sortable(),
                TextColumn::make('last_message')
                    ->label('Last message')
                    ->state(fn (User $record): ?string => $record->chatMessages()->withTrashed()->latest('id')->value('body'))
                    ->limit(60)
                    ->placeholder('No messages')
                    ->color('gray'),
            ])
            ->recordActions([
                Action::make('changeLength')
                    ->label('Change length')
                    ->icon(Heroicon::OutlinedClock)
                    ->color('gray')
                    ->schema([$this->durationField()])
                    ->action(function (User $record, array $data, ModerateChatAction $moderate): void {
                        $moderate->mute($record, (int) $data['minutes']);
                        Notification::make()->title("{$record->name} is now muted for ".self::durationLabel((int) $data['minutes']))->success()->send();
                    }),
                Action::make('unmute')
                    ->icon(Heroicon::OutlinedSpeakerWave)
                    ->color('success')
                    ->action(function (User $record, ModerateChatAction $moderate): void {
                        $moderate->unmute($record);
                        Notification::make()->title("{$record->name} can chat again")->success()->send();
                    }),
            ])
            ->toolbarActions([
                BulkAction::make('unmuteSelected')
                    ->label('Unmute selected')
                    ->icon(Heroicon::OutlinedSpeakerWave)
                    ->requiresConfirmation()
                    ->action(function (Collection $records, ModerateChatAction $moderate): void {
                        foreach ($records as $user) {
                            if ($user instanceof User) {
                                $moderate->unmute($user);
                            }
                        }
                    })
                    ->deselectRecordsAfterCompletion(),
            ])
            ->headerActions([
                Action::make('muteListener')
                    ->label('Mute a listener')
                    ->icon(Heroicon::OutlinedSpeakerXMark)
                    ->modalDescription('Search by name or email. Staff accounts cannot be muted.')
                    ->schema([
                        Select::make('user_id')
                            ->label('Listener')
                            ->required()
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => self::listenersQuery()
                                ->where(fn (Builder $query) => $query
                                    ->whereLike('name', "%{$search}%")
                                    ->orWhereLike('email', "%{$search}%"))
                                ->limit(20)
                                ->get()
                                ->mapWithKeys(fn (User $user): array => [$user->id => "{$user->name} ({$user->email})"])
                                ->all())
                            // Listeners only: a staff id fails validation instead of being muted.
                            ->getOptionLabelUsing(fn ($value): ?string => self::listenersQuery()->whereKey($value)->value('name')),
                        $this->durationField(),
                    ])
                    ->action(function (array $data, ModerateChatAction $moderate, Action $action): void {
                        $user = self::listenersQuery()->whereKey($data['user_id'])->first();

                        if (! $user) {
                            Notification::make()->title('Staff accounts cannot be muted.')->danger()->send();
                            $action->halt();

                            return;
                        }

                        $moderate->mute($user, (int) $data['minutes']);
                        Notification::make()->title("{$user->name} muted for ".self::durationLabel((int) $data['minutes']))->success()->send();
                    }),
            ])
            ->emptyStateIcon(Heroicon::OutlinedChatBubbleLeftRight)
            ->emptyStateHeading('Nobody is muted')
            ->emptyStateDescription('Mute someone from Studio Mode, or with "Mute a listener" above.');
    }

    /**
     * Accounts without an admin role.
     *
     * @return Builder<User>
     */
    private static function listenersQuery(): Builder
    {
        return User::query()->whereDoesntHave('roles', fn (Builder $query) => $query->whereIn('name', AdminRole::values()));
    }

    private function durationField(): ToggleButtons
    {
        $options = [];

        foreach (config()->array('oz.chat.mute_options_minutes') as $minutes) {
            if (is_int($minutes)) {
                $options[$minutes] = self::durationLabel($minutes);
            }
        }

        return ToggleButtons::make('minutes')
            ->label('Mute for')
            ->options($options)
            ->default(array_key_first($options))
            ->inline()
            ->required();
    }

    public static function durationLabel(int $minutes): string
    {
        return match (true) {
            $minutes >= 1440 && $minutes % 1440 === 0 => ($minutes / 1440).' day'.($minutes === 1440 ? '' : 's'),
            $minutes >= 60 && $minutes % 60 === 0 => ($minutes / 60).' hour'.($minutes === 60 ? '' : 's'),
            default => "{$minutes} minutes",
        };
    }
}
