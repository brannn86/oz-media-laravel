<?php

namespace App\Filament\Analytics\Widgets;

use App\Actions\Analytics\ProgramAudience;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Audience per host: their shows together, weighted by airtime.
 */
class HostAudienceTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Audience per host, last 30 days';

    public function table(Table $table): Table
    {
        return $table
            ->description('Each host\'s shows together, averaged by airtime.')
            ->records(fn (): array => collect(app(ProgramAudience::class)->hosts(30))->keyBy('host')->all())
            ->paginated(false)
            ->columns([
                TextColumn::make('host')
                    ->weight('medium')
                    ->description(fn (array $record): string => $record['shows']),
                TextColumn::make('airtime_hours')
                    ->label('On air')
                    ->formatStateUsing(fn (float $state): string => number_format($state, 1).' h')
                    ->alignEnd(),
                TextColumn::make('avg_icecast')
                    ->label('Avg. Icecast')
                    ->formatStateUsing(fn (?float $state): string => $state === null ? '–' : number_format($state, 1))
                    ->placeholder('–')
                    ->alignEnd(),
                TextColumn::make('avg_website')
                    ->label('Avg. website')
                    ->formatStateUsing(fn (float $state): string => number_format($state, 2))
                    ->alignEnd(),
                TextColumn::make('website_hours')
                    ->label('Website hours')
                    ->formatStateUsing(fn (float $state): string => number_format($state, 1).' h')
                    ->alignEnd(),
            ])
            ->emptyStateHeading('No hosts on the schedule yet');
    }
}
