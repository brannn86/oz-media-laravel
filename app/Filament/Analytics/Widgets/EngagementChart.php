<?php

namespace App\Filament\Analytics\Widgets;

use App\Actions\Analytics\AnalyticsMetrics;
use Filament\Widgets\ChartWidget;

class EngagementChart extends ChartWidget
{
    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Taking part, per day';

    protected ?string $description = 'Chat messages, song requests and poll votes, last 14 days.';

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $series = app(AnalyticsMetrics::class)->engagementSeries(14);

        return [
            'datasets' => [
                ChartStyle::line('Chat messages', array_column($series, 'chat'), ChartStyle::YELLOW, fill: true),
                ChartStyle::line('Song requests', array_column($series, 'requests'), ChartStyle::NAVY),
                ChartStyle::line('Poll votes', array_column($series, 'votes'), ChartStyle::RED),
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
