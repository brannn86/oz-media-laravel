<?php

namespace App\Filament\Analytics\Widgets;

use App\Actions\Analytics\SessionMetrics;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Songs website listeners stopped during most, per time aired.
 */
class SongDropOffTable extends TableWidget
{
    protected static ?string $heading = 'Songs people tune out of, last 30 days';

    public function table(Table $table): Table
    {
        return $table
            ->description('Website listening sessions that ended during each song, per time it aired (songs aired at least twice).')
            ->records(fn (): array => collect(app(SessionMetrics::class)->songDropOff(30))
                ->keyBy(fn (array $song): string => md5($song['title']."\0".$song['artist']))
                ->all())
            ->paginated(false)
            ->columns([
                TextColumn::make('title')
                    ->weight('medium')
                    ->description(fn (array $record): ?string => $record['artist']),
                TextColumn::make('tune_outs')
                    ->label('Tune-outs')
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('plays')
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('per_play')
                    ->label('Per play')
                    ->formatStateUsing(fn (float $state): string => number_format($state, 2))
                    ->alignEnd(),
            ])
            ->emptyStateHeading('No tune-outs recorded yet')
            ->emptyStateDescription('Shows up once website listeners have finished sessions during songs aired twice or more.');
    }
}
