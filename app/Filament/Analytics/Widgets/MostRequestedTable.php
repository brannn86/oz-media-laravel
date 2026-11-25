<?php

namespace App\Filament\Analytics\Widgets;

use App\Actions\Analytics\RequestMetrics;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * The songs listeners ask for most, and whether those requests were played.
 */
class MostRequestedTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Most requested songs, last 30 days';

    public function table(Table $table): Table
    {
        return $table
            ->description('The same song counts together however it was typed.')
            ->records(fn (): array => collect(app(RequestMetrics::class)->mostRequested(30))
                ->keyBy(fn (array $row): string => md5($row['song']."\0".$row['artist']))
                ->all())
            ->paginated(false)
            ->columns([
                TextColumn::make('song')
                    ->weight('medium')
                    ->description(fn (array $record): string => $record['artist']),
                TextColumn::make('requests')->numeric()->alignEnd(),
                TextColumn::make('people')->label('Listeners')->numeric()->alignEnd(),
                TextColumn::make('upvotes')->numeric()->alignEnd(),
                TextColumn::make('played')
                    ->label('Played')
                    ->formatStateUsing(fn (int $state, array $record): string => "{$state} of {$record['requests']}")
                    ->alignEnd(),
            ])
            ->emptyStateHeading('No song requests yet');
    }
}
