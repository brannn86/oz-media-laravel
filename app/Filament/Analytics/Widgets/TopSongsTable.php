<?php

namespace App\Filament\Analytics\Widgets;

use App\Models\TrackPlay;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\DB;

/**
 * Songs aired most in the last 7 days, with the Icecast audience they had.
 */
class TopSongsTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Most played songs, last 7 days';

    public function table(Table $table): Table
    {
        $since = now('Asia/Jakarta')->subDays(6)->startOfDay()->setTimezone(config('app.timezone'));

        return $table
            ->query(
                TrackPlay::query()
                    ->where('started_at', '>=', $since)
                    ->select([
                        DB::raw('MIN(id) as id'),
                        'title',
                        'artist',
                        DB::raw('COUNT(*) as plays'),
                        DB::raw('AVG(listeners) as average_listeners'),
                        DB::raw('MAX(started_at) as last_played_at'),
                    ])
                    ->groupBy('title', 'artist')
                    ->orderByDesc('plays')
                    ->orderByDesc('last_played_at')
            )
            // Grouped rows: no sorting by the (aggregated) id.
            ->defaultKeySort(false)
            ->paginated([10])
            ->columns([
                TextColumn::make('title')
                    ->weight('medium')
                    ->description(fn (TrackPlay $record): ?string => $record->artist),
                TextColumn::make('plays')
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('average_listeners')
                    ->label('Avg. Icecast listeners')
                    ->formatStateUsing(fn (mixed $state): string => $state === null ? '–' : number_format((float) $state, 1))
                    ->alignEnd(),
            ])
            ->emptyStateHeading('No songs recorded yet')
            ->emptyStateDescription('Songs are read from the Icecast stream every 15 seconds.');
    }
}
