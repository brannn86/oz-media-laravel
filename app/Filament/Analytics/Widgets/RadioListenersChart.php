<?php

namespace App\Filament\Analytics\Widgets;

use App\Actions\Analytics\AnalyticsMetrics;
use Filament\Widgets\ChartWidget;

class RadioListenersChart extends ChartWidget
{
    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Radio listeners per day';

    protected ?string $description = 'Icecast: average and highest of the counts taken at the start of every song. Website: peak of people with the site\'s player on. Last 14 days.';

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $metrics = app(AnalyticsMetrics::class);
        $icecast = $metrics->icecastSeries(14);
        $website = $metrics->websitePeakSeries(14);

        return [
            'datasets' => [
                ChartStyle::line('Icecast, highest', array_column($icecast, 'highest'), ChartStyle::NAVY),
                ChartStyle::line('Icecast, average', array_column($icecast, 'average'), ChartStyle::YELLOW, fill: true),
                ChartStyle::line('Website, peak', array_column($website, 'peak'), ChartStyle::RED),
            ],
            'labels' => app(AnalyticsMetrics::class)->dayLabels(14),
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
