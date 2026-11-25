<?php

namespace App\Filament\Analytics\Widgets;

use App\Actions\Analytics\AcquisitionMetrics;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Weekly cohorts: new listeners each week and how many were back later.
 */
class CohortTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Weekly cohorts';

    public function table(Table $table): Table
    {
        $percent = fn (?float $state): string => $state === null ? '–' : number_format($state, 0).'%';

        return $table
            ->description('New listeners each week (Monday to Sunday, WIB) and the share who listened again 1–4 weeks later; sign-ups and the share who listened (signed in) the week after. "–": that week isn\'t over yet.')
            ->records(fn (): array => collect(app(AcquisitionMetrics::class)->weeklyCohorts(8))->keyBy('week')->all())
            ->paginated(false)
            ->columns([
                TextColumn::make('week')->weight('medium'),
                TextColumn::make('new_listeners')->label('New listeners')->numeric()->alignEnd(),
                TextColumn::make('week_1')->label('Week 1')->formatStateUsing($percent)->placeholder('–')->alignEnd(),
                TextColumn::make('week_2')->label('Week 2')->formatStateUsing($percent)->placeholder('–')->alignEnd(),
                TextColumn::make('week_3')->label('Week 3')->formatStateUsing($percent)->placeholder('–')->alignEnd(),
                TextColumn::make('week_4')->label('Week 4')->formatStateUsing($percent)->placeholder('–')->alignEnd(),
                TextColumn::make('signups')->label('Sign-ups')->numeric()->alignEnd(),
                TextColumn::make('signups_back')->label('Sign-ups back next week')->formatStateUsing($percent)->placeholder('–')->alignEnd(),
            ]);
    }
}
