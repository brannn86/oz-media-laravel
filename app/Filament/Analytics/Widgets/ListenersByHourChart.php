<?php

namespace App\Filament\Analytics\Widgets;

use App\Actions\Analytics\AnalyticsMetrics;
use Filament\Widgets\ChartWidget;

class ListenersByHourChart extends ChartWidget
{
    protected ?string $heading = 'Listeners by hour of day';

    protected ?string $description = 'Average over the last 7 days, WIB. Icecast from its count every 15 minutes; website from the players on the site.';

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $byHour = app(AnalyticsMetrics::class)->listenersByHour(7);

        return [
            'datasets' => [
                ChartStyle::line('Icecast', $byHour['icecast'], ChartStyle::NAVY),
                ChartStyle::line('Website', $byHour['website'], ChartStyle::YELLOW, fill: true),
            ],
            'labels' => array_map(fn (int $hour): string => sprintf('%02d:00', $hour), range(0, 23)),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            ...ChartStyle::countAxes(),
            'spanGaps' => true,
        ];
    }
}
