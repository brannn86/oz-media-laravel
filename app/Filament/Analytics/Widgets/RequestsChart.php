<?php

namespace App\Filament\Analytics\Widgets;

use App\Actions\Analytics\AnalyticsMetrics;
use App\Actions\Analytics\RequestMetrics;
use Filament\Widgets\ChartWidget;

class RequestsChart extends ChartWidget
{
    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Requested and played, per day';

    protected ?string $description = 'Song requests made, and requests that went on air, last 14 days.';

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '260px';

    protected function getData(): array
    {
        $daily = app(RequestMetrics::class)->daily(14);

        return [
            'datasets' => [
                ChartStyle::bars('Requested', array_column($daily, 'requested'), ChartStyle::YELLOW),
                ChartStyle::bars('Played', array_column($daily, 'played'), ChartStyle::NAVY),
            ],
            'labels' => app(AnalyticsMetrics::class)->dayLabels(14),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return ChartStyle::countAxes();
    }
}
