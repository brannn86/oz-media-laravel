<?php

namespace App\Filament\Analytics\Widgets;

use App\Actions\Analytics\AnalyticsMetrics;
use App\Actions\Analytics\DashboardMetrics;
use App\Enums\AudienceMode;
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
        $requests = $metrics->songRequests(7);

        return [
            Stat::make('Icecast listeners now', $icecast === null ? '–' : number_format($icecast['listeners']))
                ->description($icecast === null ? 'Icecast is not reporting' : 'Peak '.number_format($icecast['peak']).' since the stream started')
                ->descriptionIcon(Heroicon::Radio)
                ->color($icecast === null ? 'gray' : 'success'),
            Stat::make('Website listeners now', $website === null ? '–' : number_format($website))
                ->description('Most at once, 7 days: '.number_format($metrics->peakConcurrent(AudienceMode::Audio, 7)))
                ->descriptionIcon(Heroicon::Signal)
                ->color($website === null ? 'gray' : 'success'),
            Stat::make('Listening time on the site, 7 days', number_format($metrics->audienceHours(AudienceMode::Audio, 7), 1).' h')
                ->description(number_format($metrics->uniqueAudience(AudienceMode::Audio, 7)).' different listeners; '.number_format(intdiv($metrics->listeningMinutes(7), 60)).' h by signed-in ones')
                ->descriptionIcon(Heroicon::Clock)
                ->color('primary'),
            Stat::make('Song requests, 7 days', number_format($requests['submitted']))
                ->description(number_format($requests['played']).' played, '.number_format($metrics->songsPlayed(7)).' songs aired')
                ->descriptionIcon(Heroicon::MusicalNote),
        ];
    }
}
