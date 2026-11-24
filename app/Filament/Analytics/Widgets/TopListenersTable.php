<?php

namespace App\Filament\Analytics\Widgets;

use App\Enums\PointReason;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * The most loyal signed-in listeners: minutes of radio in the last 30 days.
 */
class TopListenersTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Most loyal listeners, last 30 days';

    public function table(Table $table): Table
    {
        $since = now('Asia/Jakarta')->subDays(29)->startOfDay()->setTimezone(config('app.timezone'));
        $listening = fn (Builder $query) => $query
            ->where('reason', PointReason::Listening)
            ->where('created_at', '>=', $since);

        return $table
            ->query(
                User::query()
                    ->doesntHave('roles')
                    ->whereHas('pointTransactions', $listening)
                    ->withCount(['pointTransactions as minutes' => $listening])
                    ->orderByDesc('minutes')
            )
            ->paginated([10])
            ->columns([
                TextColumn::make('name')
                    ->weight('medium')
                    ->description(fn (User $record): string => $record->email),
                TextColumn::make('minutes')
                    ->label('Listening time')
                    ->formatStateUsing(fn (int $state): string => $state >= 60 ? intdiv($state, 60).' h '.($state % 60).' min' : "{$state} min")
                    ->alignEnd(),
                TextColumn::make('current_streak_days')
                    ->label('Streak')
                    ->formatStateUsing(fn (int $state): string => $state > 0 ? "{$state} days" : '–')
                    ->alignEnd(),
                TextColumn::make('points_balance')
                    ->label('OZ Poin')
                    ->numeric()
                    ->alignEnd(),
            ])
            ->emptyStateHeading('No listening yet')
            ->emptyStateDescription('Signed-in listeners appear here after a minute of radio.');
    }
}
