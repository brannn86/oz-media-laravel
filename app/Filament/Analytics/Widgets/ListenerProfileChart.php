<?php

namespace App\Filament\Analytics\Widgets;

use App\Actions\Analytics\ListenerProfileMetrics;
use Filament\Widgets\ChartWidget;

/**
 * Accounts beside the people who actually listened, per age range or
 * gender: ListenerProfileChart::make(['dimension' => 'gender']).
 */
class ListenerProfileChart extends ChartWidget
{
    public string $dimension = 'age_range';

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '280px';

    public function getHeading(): string
    {
        return ListenerProfileMetrics::DIMENSIONS[$this->dimension] ?? 'Listeners';
    }

    public function getDescription(): string
    {
        $coverage = app(ListenerProfileMetrics::class)->coverage(30);

        if ($coverage['sessions'] === 0) {
            return 'Signed-up listeners, and who of them listened or watched in the last 30 days. No listening yet.';
        }

        $share = (int) round($coverage['profiled'] / $coverage['sessions'] * 100);

        return "Signed-up listeners, and who of them listened or watched in the last 30 days. Guests aren't included: {$share}% of the 30 days' sessions were signed-in listeners with these details.";
    }

    protected function getData(): array
    {
        $rows = app(ListenerProfileMetrics::class)->breakdown($this->dimension, 30);

        return [
            'datasets' => [
                ChartStyle::bars('Accounts', array_column($rows, 'accounts'), ChartStyle::NAVY),
                ChartStyle::bars('Listened, 30 days', array_column($rows, 'listeners'), ChartStyle::YELLOW),
            ],
            'labels' => array_column($rows, 'label'),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            ...ChartStyle::countAxes(),
            'plugins' => ['legend' => ['position' => 'bottom']],
        ];
    }
}
