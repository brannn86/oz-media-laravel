<?php

namespace App\Filament\Analytics\Widgets;

use App\Actions\Analytics\DeviceMetrics;
use Filament\Widgets\ChartWidget;

/**
 * Sessions on the site and app by one device dimension (device, platform,
 * OS or browser): DeviceBreakdownChart::make(['dimension' => 'browser']).
 */
class DeviceBreakdownChart extends ChartWidget
{
    public string $dimension = 'device';

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '260px';

    public function getHeading(): string
    {
        return DeviceMetrics::DIMENSIONS[$this->dimension] ?? 'Devices';
    }

    public function getDescription(): string
    {
        $rows = app(DeviceMetrics::class)->breakdown($this->dimension, 30);

        return $rows === []
            ? 'Listening and watching sessions, last 30 days. None yet.'
            : number_format(array_sum(array_column($rows, 'sessions'))).' listening and watching sessions, last 30 days.';
    }

    protected function getData(): array
    {
        $rows = app(DeviceMetrics::class)->breakdown($this->dimension, 30);
        $palette = [ChartStyle::YELLOW, ChartStyle::NAVY, ChartStyle::RED, ChartStyle::INK, '#fcf48e', '#8a94b8', '#e9a29b', '#b5b5b0'];

        return [
            'datasets' => [[
                'label' => 'Sessions',
                'data' => array_column($rows, 'sessions'),
                'backgroundColor' => array_map(fn (int $i): string => $palette[$i % count($palette)], array_keys($rows)),
                'borderWidth' => 0,
            ]],
            'labels' => array_column($rows, 'label'),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['position' => 'right']],
            'scales' => ['x' => ['display' => false], 'y' => ['display' => false]],
        ];
    }
}
