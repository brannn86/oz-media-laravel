<?php

namespace App\Filament\Analytics\Widgets;

use App\Actions\Analytics\AnalyticsMetrics;
use App\Enums\AudienceMode;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class VideoStats extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected ?string $description = 'People with the video open on the site, guests included (intermission time counts too). Counted from 24 Nov 2026.';

    protected function getStats(): array
    {
        $metrics = app(AnalyticsMetrics::class);
        $video = AudienceMode::Video;

        return [
            Stat::make('Viewers, 7 days', number_format($metrics->uniqueAudience($video, 7)))
                ->description(number_format($metrics->uniqueAudience($video, 1)).' today, '.number_format($metrics->uniqueAudience($video, 30)).' in 30 days')
                ->descriptionIcon(Heroicon::Users)
                ->color('primary'),
            Stat::make('Watch time, 7 days', number_format($metrics->audienceHours($video, 7), 1).' h')
                ->description(number_format($metrics->audienceHours($video, 1), 1).' h today')
                ->descriptionIcon(Heroicon::Clock),
            Stat::make('Most watching at once, 7 days', number_format($metrics->peakConcurrent($video, 7)))
                ->description(number_format($metrics->peakConcurrent($video, 1)).' today')
                ->descriptionIcon(Heroicon::ArrowTrendingUp)
                ->color('success'),
            Stat::make('Live reactions, 7 days', number_format($metrics->reactions(7)))
                ->description(number_format($metrics->reactions(1)).' today; '.number_format($metrics->stationsWithVideo()).' '.($metrics->stationsWithVideo() === 1 ? 'station offers' : 'stations offer').' video')
                ->descriptionIcon(Heroicon::Heart)
                ->color('danger'),
        ];
    }
}
