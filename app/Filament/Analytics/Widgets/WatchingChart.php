<?php

namespace App\Filament\Analytics\Widgets;

use App\Actions\Analytics\AnalyticsMetrics;
use Filament\Widgets\ChartWidget;

class WatchingChart extends ChartWidget
{
    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Watching per day';

    protected ?string $description = 'Hours of video watched on the site and different people watching, last 14 days.';

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $metrics = app(AnalyticsMetrics::class);
        $series = $metrics->playerSeries(14);

        return [
            'datasets' => [
                ChartStyle::bars('Hours watched', array_column($series, 'video_hours'), ChartStyle::YELLOW),
                [...ChartStyle::line('Viewers', array_column($series, 'video_people'), ChartStyle::NAVY), 'type' => 'line'],
            ],
            'labels' => $metrics->dayLabels(14),
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
