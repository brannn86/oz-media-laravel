<?php

namespace App\Filament\Pages\Analytics;

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
