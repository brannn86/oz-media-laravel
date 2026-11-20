<?php

namespace App\Filament\Resources\RewardRedemptions;

use App\Actions\Rewards\ManageRedemptionAction;
use App\Enums\RedemptionStatus;
use App\Filament\NavigationGroup;
use App\Filament\Resources\RewardRedemptions\Pages\ManageRewardRedemptions;
use App\Models\RewardRedemption;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;
use UnitEnum;

/**
 * Redemptions arrive from listeners; admins hand them over or cancel them
 * (cancelling refunds the points and restocks the reward).
 */
class RewardRedemptionResource extends Resource
{
    protected static ?string $model = RewardRedemption::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Loyalty;

    protected static ?string $navigationLabel = 'Redemptions';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $pending = RewardRedemption::query()->where('status', RedemptionStatus::Pending)->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Waiting to be handed over';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['reward', 'user']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Redeemed')->dateTime('d M Y, H:i', timezone: 'Asia/Jakarta')->sortable(),
                TextColumn::make('user.name')
                    ->label('Listener')
                    ->searchable()
                    ->description(fn (RewardRedemption $record): ?string => $record->user?->email),
                TextColumn::make('reward.name')->label('Reward')->searchable()->weight('medium'),
                TextColumn::make('points_spent')->label('Points')->suffix(' poin'),
                TextColumn::make('listener_note')->label('Listener note')->limit(40)->placeholder('—')->wrap(),
                TextColumn::make('status')->badge(),
                TextColumn::make('admin_note')->label('Admin note')->limit(40)->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options(RedemptionStatus::class)->default(RedemptionStatus::Pending->value),
                SelectFilter::make('reward')->relationship('reward', 'name'),
            ])
            ->recordActions([
                Action::make('fulfill')
                    ->label('Mark handed over')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (RewardRedemption $record): bool => $record->status === RedemptionStatus::Pending)
                    ->authorize('update')
                    ->schema([Textarea::make('admin_note')->label('Note (optional)')->rows(2)->helperText('E.g. collected at the studio, courier tracking number.')])
                    ->action(fn (RewardRedemption $record, array $data, ManageRedemptionAction $manage) => self::run(
                        fn () => $manage->fulfill($record, $data['admin_note'] ?? null),
                        'Marked as handed over',
                    )),
                Action::make('cancel')
                    ->label('Cancel & refund')
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->color('danger')
                    ->visible(fn (RewardRedemption $record): bool => $record->status === RedemptionStatus::Pending)
                    ->authorize('update')
                    ->requiresConfirmation()
                    ->modalDescription(fn (RewardRedemption $record): string => "{$record->points_spent} OZ Poin go back to {$record->user?->name} and the reward returns to stock.")
                    ->schema([Textarea::make('admin_note')->label('Reason (optional)')->rows(2)])
                    ->action(fn (RewardRedemption $record, array $data, ManageRedemptionAction $manage) => self::run(
                        fn () => $manage->cancel($record, $data['admin_note'] ?? null),
                        'Cancelled and refunded',
                    )),
            ])
            ->emptyStateHeading('No redemptions here')
            ->emptyStateDescription('When listeners trade OZ Poin for rewards they show up here.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageRewardRedemptions::route('/'),
        ];
    }

    private static function run(callable $change, string $done): void
    {
        try {
            $change();
            Notification::make()->title($done)->success()->send();
        } catch (ValidationException $e) {
            Notification::make()->title(collect($e->errors())->flatten()->first())->danger()->send();
        }
    }
}
