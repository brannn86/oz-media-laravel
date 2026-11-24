<?php

namespace App\Filament\Analytics\Widgets;

use App\Actions\Analytics\AnalyticsMetrics;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class NewsStats extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected ?string $description = 'One view per visitor per article every 30 minutes.';

    protected function getStats(): array
    {
        $metrics = app(AnalyticsMetrics::class);
        $week = $metrics->articleViews(7);
        $published = $metrics->articlesPublished(30);
        $sections = $metrics->viewsByCategory(30);
        $topSection = array_key_first($sections);

        return [
            Stat::make('Article views, 7 days', number_format($week))
                ->description(number_format($metrics->articleViews(1)).' today, '.number_format($metrics->articleViews(30)).' in 30 days')
                ->descriptionIcon(Heroicon::Eye)
                ->color('primary'),
            Stat::make('Articles published, 30 days', number_format($published))
                ->description(number_format($metrics->articlesPublished(7)).' this week')
                ->descriptionIcon(Heroicon::Newspaper),
            Stat::make('Most-read section, 30 days', $topSection ?? '–')
                ->description($topSection === null ? 'No views yet' : number_format($sections[$topSection]).' views')
                ->descriptionIcon(Heroicon::ChartBar),
        ];
    }
}
