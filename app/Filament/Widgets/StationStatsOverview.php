<?php

namespace App\Filament\Widgets;

use App\Actions\Analytics\DashboardMetrics;
use App\Filament\Pages\StudioMode;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Headline numbers across all stations, today (WIB).
 */
class StationStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $metrics = app(DashboardMetrics::class);
        $today = $metrics->today();
        $series = $metrics->dailySeries(7);
        $live = $metrics->liveListeners();

        return [
            Stat::make('Listening now', $live === null ? '–' : number_format($live))
                ->description($live === null ? 'Reverb is not reachable' : 'Player on, guests included')
                ->descriptionIcon(Heroicon::Signal)
                ->color($live === null ? 'gray' : 'success'),
            Stat::make('Signed-in listeners today', number_format($metrics->signedInListenersOn($today)))
                ->description('Peak live today: '.number_format($metrics->peakListenersOn($today)))
                ->descriptionIcon(Heroicon::Users)
                ->chart(array_map(fn (array $day): float => $day['signed_in'], $series))
                ->color('primary'),
            Stat::make('OZ Poin earned today', number_format($metrics->pointsEarnedOn($today)))
                ->description(number_format($metrics->pointsEarnedAllTime()).' all time')
                ->descriptionIcon(Heroicon::CurrencyDollar)
                ->color('warning'),
            Stat::make('Song requests waiting', number_format($metrics->openSongRequests()))
                ->description('Waiting or coming up, all stations')
                ->descriptionIcon(Heroicon::MusicalNote)
                ->url(StudioMode::getUrl()),
        ];
    }
}
