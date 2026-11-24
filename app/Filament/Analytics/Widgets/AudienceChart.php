<?php

namespace App\Filament\Analytics\Widgets;

use App\Actions\Analytics\AnalyticsMetrics;
use Filament\Widgets\ChartWidget;

class AudienceChart extends ChartWidget
{
    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Sign-ups and active listeners';

    protected ?string $description = 'Per WIB day, last 30 days.';

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $series = app(AnalyticsMetrics::class)->audienceSeries(30);

        return [
            'datasets' => [
                ChartStyle::line('Active listeners', array_column($series, 'active'), ChartStyle::YELLOW, fill: true),
                ChartStyle::line('New sign-ups', array_column($series, 'signups'), ChartStyle::NAVY),
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
