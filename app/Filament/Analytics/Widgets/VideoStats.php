<?php

namespace App\Filament\Analytics\Widgets;

use App\Actions\Analytics\AnalyticsMetrics;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class VideoStats extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected ?string $description = 'Viewers and watch time aren\'t measured yet; reactions are counted from 24 Nov 2026.';

    protected function getStats(): array
    {
        $metrics = app(AnalyticsMetrics::class);

        return [
            Stat::make('Stations offering video', number_format($metrics->stationsWithVideo()))
                ->description('A video stream URL is set')
                ->descriptionIcon(Heroicon::VideoCamera),
            Stat::make('Live reactions, 7 days', number_format($metrics->reactions(7)))
                ->description(number_format($metrics->reactions(1)).' today, '.number_format($metrics->reactions(30)).' in 30 days')
                ->descriptionIcon(Heroicon::Heart)
                ->color('danger'),
        ];
    }
}
