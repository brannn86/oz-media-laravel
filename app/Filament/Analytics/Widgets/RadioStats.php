<?php

namespace App\Filament\Analytics\Widgets;

use App\Actions\Analytics\AnalyticsMetrics;
use App\Actions\Analytics\DashboardMetrics;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RadioStats extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '30s';

    protected ?string $description = 'Icecast counts every player (site, apps, smart speakers); the website count is people with this site\'s player on.';

    protected function getStats(): array
    {
        $dashboard = app(DashboardMetrics::class);
        $metrics = app(AnalyticsMetrics::class);
        $icecast = $dashboard->icecastAudience();
        $website = $dashboard->liveListeners();
        $minutes = $metrics->listeningMinutes(7);
        $requests = $metrics->songRequests(7);

        return [
            Stat::make('Icecast listeners now', $icecast === null ? '–' : number_format($icecast['listeners']))
                ->description($icecast === null ? 'Icecast is not reporting' : 'Peak '.number_format($icecast['peak']).' since the stream started')
                ->descriptionIcon(Heroicon::Radio)
                ->color($icecast === null ? 'gray' : 'success'),
            Stat::make('Website listeners now', $website === null ? '–' : number_format($website))
                ->description('Peak today: '.number_format($dashboard->peakListenersOn($dashboard->today())))
                ->descriptionIcon(Heroicon::Signal)
                ->color($website === null ? 'gray' : 'success'),
            Stat::make('Listening time, 7 days', number_format(intdiv($minutes, 60)).' h')
                ->description('Signed-in listeners; '.number_format(intdiv($metrics->listeningMinutes(1), 60)).' h today')
                ->descriptionIcon(Heroicon::Clock)
                ->color('primary'),
            Stat::make('Song requests, 7 days', number_format($requests['submitted']))
                ->description(number_format($requests['played']).' played, '.number_format($metrics->songsPlayed(7)).' songs aired')
                ->descriptionIcon(Heroicon::MusicalNote),
        ];
    }
}
