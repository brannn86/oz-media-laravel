<?php

namespace App\Filament\Analytics\Widgets;

use App\Actions\Analytics\AcquisitionMetrics;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Where listeners come from: listening and watching sessions per source.
 */
class SourcesTable extends TableWidget
{
    protected static ?string $heading = 'Where listeners come from, last 30 days';

    public function table(Table $table): Table
    {
        return $table
            ->description('The UTM source of the link, else the referring site, else Direct (typed in, bookmarks, apps that hide where they came from).')
            ->records(fn (): array => collect(app(AcquisitionMetrics::class)->sources(30))->keyBy('source')->all())
            ->paginated(false)
            ->columns([
                TextColumn::make('source')->weight('medium'),
                TextColumn::make('sessions')->numeric()->alignEnd(),
                TextColumn::make('people')->label('Listeners')->numeric()->alignEnd(),
                TextColumn::make('hours')
                    ->formatStateUsing(fn (float $state): string => number_format($state, 1).' h')
                    ->alignEnd(),
                TextColumn::make('median_minutes')
                    ->label('Typical session')
                    ->formatStateUsing(fn (float $state): string => number_format($state, 0).' min')
                    ->alignEnd(),
            ])
            ->emptyStateHeading('No listening sessions yet');
    }
}
