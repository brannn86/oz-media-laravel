<?php

namespace App\Filament\Widgets;

use App\Actions\Analytics\DashboardMetrics;
use Filament\Widgets\ChartWidget;

/**
 * Daily active listeners over the last two weeks (WIB days).
 */
class ListenersChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Daily listeners';

    protected ?string $description = 'Signed-in listeners (1+ minute of playback) and the peak live audience, last 14 days.';

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '260px';

    protected function getData(): array
    {
        $series = app(DashboardMetrics::class)->dailySeries(14);

        return [
            'datasets' => [
                [
                    'label' => 'Signed-in listeners',
                    'data' => array_column($series, 'signed_in'),
                    'borderColor' => '#f2bd51',
                    'backgroundColor' => 'rgba(242, 189, 81, 0.15)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Peak live listeners',
                    'data' => array_column($series, 'peak'),
                    'borderColor' => '#141f43',
                    'backgroundColor' => 'rgba(20, 31, 67, 0.05)',
                    'tension' => 0.3,
                ],
            ],
            'labels' => array_column($series, 'label'),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    /**
     * Counts of people: start at zero, whole numbers only.
     *
     * @return array<string, mixed>
     */
    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'suggestedMax' => 5,
                    'ticks' => ['precision' => 0],
                ],
            ],
        ];
    }
}
