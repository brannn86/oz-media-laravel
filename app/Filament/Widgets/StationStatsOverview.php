<?php

namespace App\Filament\Widgets;

use App\Actions\Analytics\DashboardMetrics;
use App\Filament\Pages\StudioMode;
use Carbon\CarbonImmutable;
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
        $icecast = $metrics->icecastAudience();

        return [
            Stat::make('Listening now', $live === null ? '–' : number_format($live))
                ->description($live === null ? 'Reverb is not reachable' : 'Website player on, guests included')
                ->descriptionIcon(Heroicon::Signal)
                ->color($live === null ? 'gray' : 'success'),
            Stat::make('Listeners (Icecast)', $icecast === null ? '–' : number_format($icecast['listeners']))
                ->description($icecast === null
                    ? 'Icecast is not reporting'
                    : collect($icecast['stations'])->map(fn (array $station): string => "{$station['name']} {$station['listeners']}")->implode(' · '))
                ->descriptionIcon(Heroicon::Radio)
                ->color($icecast === null ? 'gray' : 'success'),
            Stat::make('Peak listeners (Icecast)', $icecast === null ? '–' : number_format($icecast['peak']))
                ->description($icecast === null ? 'Icecast is not reporting' : $this->peakSince($icecast['stations']))
                ->descriptionIcon(Heroicon::ArrowTrendingUp)
                ->color($icecast === null ? 'gray' : 'primary'),
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

    /**
     * Icecast counts its peak from when the stream last (re)started, not per day.
     *
     * @param  list<array{name: string, listeners: int, peak: int|null, since: string|null}>  $stations
     */
    private function peakSince(array $stations): string
    {
        if (count($stations) === 1 && $stations[0]['since'] !== null) {
            return 'Since the stream started '.CarbonImmutable::parse($stations[0]['since'])
                ->timezone(DashboardMetrics::TIMEZONE)
                ->format('j M, H:i').' WIB';
        }

        return collect($stations)
            ->map(fn (array $station): string => "{$station['name']} ".($station['peak'] ?? $station['listeners']))
            ->implode(' · ').', since each stream started';
    }
}
