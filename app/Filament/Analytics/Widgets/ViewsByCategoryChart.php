<?php

namespace App\Filament\Analytics\Widgets;

use App\Actions\Analytics\AnalyticsMetrics;
use Filament\Widgets\ChartWidget;

class ViewsByCategoryChart extends ChartWidget
{
    protected ?string $heading = 'Views by section';

    protected ?string $description = 'Last 30 days, most read first.';

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $views = app(AnalyticsMetrics::class)->viewsByCategory(30);

        return [
            'datasets' => [
                ChartStyle::bars('Views', array_values($views), ChartStyle::NAVY),
            ],
            'labels' => array_keys($views),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => ['legend' => ['display' => false]],
            'scales' => [
                'x' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]],
            ],
        ];
    }
}
