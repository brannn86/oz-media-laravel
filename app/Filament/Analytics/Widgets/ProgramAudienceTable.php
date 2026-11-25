<?php

namespace App\Filament\Analytics\Widgets;

use App\Actions\Analytics\ProgramAudience;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Audience per show, last 30 days, by the current weekly schedule.
 */
class ProgramAudienceTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Audience per show, last 30 days';

    public function table(Table $table): Table
    {
        return $table
            ->description('Icecast samples (every 15 min), website listening minutes and tune-outs (finished website listening sessions), credited to the show on air at the time by the current schedule.')
            ->records(fn (): array => collect(app(ProgramAudience::class)->programs(30))->keyBy('program_id')->all())
            ->paginated(false)
            ->columns([
                TextColumn::make('program')
                    ->label('Show')
                    ->weight('medium')
                    ->description(fn (array $record): string => trim($record['station'].($record['hosts'] !== '' ? " · {$record['hosts']}" : ''))),
                TextColumn::make('airtime_hours')
                    ->label('On air')
                    ->formatStateUsing(fn (float $state): string => number_format($state, 1).' h')
                    ->alignEnd(),
                TextColumn::make('avg_icecast')
                    ->label('Avg. Icecast')
                    ->formatStateUsing(fn (?float $state): string => $state === null ? '–' : number_format($state, 1))
                    ->placeholder('–')
                    ->alignEnd(),
                TextColumn::make('peak_icecast')
                    ->label('Peak Icecast')
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
                TextColumn::make('tune_outs_per_hour')
                    ->label('Tune-outs / h')
                    ->tooltip('Website listeners who stopped during the show, per hour on air.')
                    ->formatStateUsing(fn (float $state): string => number_format($state, 2))
                    ->alignEnd(),
                TextColumn::make('early_tune_outs')
                    ->label('Left in first 10 min')
                    ->tooltip('Website listeners who stopped within 10 minutes of the show starting.')
                    ->numeric()
                    ->alignEnd(),
            ])
            ->emptyStateHeading('No shows on the schedule yet');
    }
}
