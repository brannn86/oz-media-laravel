<?php

namespace App\Filament\Analytics\Widgets;

use App\Actions\Analytics\AnalyticsMetrics;
use Filament\Widgets\ChartWidget;

class ListeningChart extends ChartWidget
{
    protected ?string $heading = 'Radio on the site, per day';

    protected ?string $description = 'Hours listened and different people listening (guests included), last 14 days.';

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $metrics = app(AnalyticsMetrics::class);
        $series = $metrics->playerSeries(14);

        return [
            'datasets' => [
                ChartStyle::bars('Hours listened', array_column($series, 'audio_hours'), ChartStyle::YELLOW),
                [...ChartStyle::line('Listeners', array_column($series, 'audio_people'), ChartStyle::NAVY), 'type' => 'line'],
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
