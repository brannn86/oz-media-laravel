<?php

namespace App\Filament\Analytics\Widgets;

use App\Actions\Analytics\ListenerProfileMetrics;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Where signed-up listeners live, and how much they listened.
 */
class ListenerLocationsTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Where listeners live, last 30 days';

    public function table(Table $table): Table
    {
        return $table
            ->description('From the sign-up details (guests not included). Areas with fewer than '.ListenerProfileMetrics::MIN_PEOPLE_PER_AREA.' people are counted together under Other areas, so no one can be picked out.')
            ->records(fn (): array => collect(app(ListenerProfileMetrics::class)->locations(30))->keyBy('label')->all())
            ->paginated(false)
            ->columns([
                TextColumn::make('label')->label('Area')->weight('medium'),
                TextColumn::make('accounts')->label('Accounts')->numeric()->alignEnd(),
                TextColumn::make('listeners')->label('Listened')->numeric()->alignEnd(),
                TextColumn::make('hours')
                    ->formatStateUsing(fn (float $state): string => number_format($state, 1).' h')
                    ->alignEnd(),
            ])
            ->emptyStateHeading('No listener locations yet')
            ->emptyStateDescription('They appear as listeners sign up with an area.');
    }
}
