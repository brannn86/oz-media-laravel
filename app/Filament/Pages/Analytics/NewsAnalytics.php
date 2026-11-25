<?php

namespace App\Filament\Pages\Analytics;

use App\Actions\Analytics\AnalyticsMetrics;
use App\Filament\Analytics\AnalyticsPage;
use App\Filament\Analytics\Widgets\ArticleViewsChart;
use App\Filament\Analytics\Widgets\NewsStats;
use App\Filament\Analytics\Widgets\ViewsByCategoryChart;
use App\Filament\Widgets\TopArticlesWidget;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

/**
 * OZ News: views, what gets read, which sections.
 */
class NewsAnalytics extends AnalyticsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static ?string $navigationLabel = 'News';

    protected static ?string $title = 'News analytics';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'analytics/news';

    protected function exports(): array
    {
        return [
            'Daily views, 30 days' => function (): array {
                $metrics = app(AnalyticsMetrics::class);

                return [
                    ['Date', 'Article views'],
                    array_map(fn (string $date, array $day): array => [$date, $day['views']], $metrics->dates(30), $metrics->articleViewSeries(30)),
                ];
            },
            'Views by section, 30 days' => function (): array {
                $views = app(AnalyticsMetrics::class)->viewsByCategory(30);

                return [['Section', 'Views'], array_map(null, array_keys($views), array_values($views))];
            },
            'Articles by views, 30 days' => fn (): array => [
                ['Title', 'Station', 'Section', 'Published (WIB)', 'Views'],
                array_map(fn (array $row): array => array_values($row), app(AnalyticsMetrics::class)->topArticles(30)),
            ],
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            NewsStats::class,
            ArticleViewsChart::class,
            ViewsByCategoryChart::class,
            TopArticlesWidget::class,
        ];
    }
}
