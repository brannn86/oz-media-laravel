<?php

namespace App\Filament\Analytics\Widgets;

use App\Actions\Analytics\AnalyticsMetrics;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Per article: views, reads, reading time and how many finished it.
 */
class ArticleReadingTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'How each article is read, last 30 days';

    public function table(Table $table): Table
    {
        return $table
            ->description('Reading time counts only while the tab is open and someone is scrolling or moving; finished means 90% of the text or more.')
            ->records(fn (): array => collect(app(AnalyticsMetrics::class)->articleReading(30))
                ->keyBy(fn (array $row): string => md5($row['title']."\0".$row['station']))
                ->all())
            ->paginated([10, 25])
            ->columns([
                TextColumn::make('title')
                    ->weight('medium')
                    ->wrap()
                    ->description(fn (array $record): ?string => $record['station']),
                TextColumn::make('views')->numeric()->alignEnd(),
                TextColumn::make('reads')->numeric()->alignEnd(),
                TextColumn::make('average_seconds')
                    ->label('Avg. reading time')
                    ->formatStateUsing(fn (int $state): string => $state >= 60 ? intdiv($state, 60).' min '.($state % 60).' s' : "{$state} s")
                    ->alignEnd(),
                TextColumn::make('finished')
                    ->label('Finished')
                    ->formatStateUsing(fn (?float $state): string => $state === null ? '–' : number_format($state, 0).'%')
                    ->alignEnd(),
            ])
            ->emptyStateHeading('No reading recorded yet')
            ->emptyStateDescription('Shows up as readers open and leave articles.');
    }
}
