<?php

namespace App\Filament\Analytics\Widgets;

use App\Actions\Analytics\AnalyticsMetrics;
use Filament\Widgets\ChartWidget;

class ArticleViewsChart extends ChartWidget
{
    protected ?string $heading = 'Article views per day';

    protected ?string $description = 'Last 30 days.';

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $series = app(AnalyticsMetrics::class)->articleViewSeries(30);

        return [
            'datasets' => [
                ChartStyle::line('Views', array_column($series, 'views'), ChartStyle::YELLOW, fill: true),
            ],
            'labels' => app(AnalyticsMetrics::class)->dayLabels(30),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return ChartStyle::countAxes();
    }
}
