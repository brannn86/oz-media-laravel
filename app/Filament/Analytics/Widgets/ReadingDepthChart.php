<?php

namespace App\Filament\Analytics\Widgets;

use App\Actions\Analytics\AnalyticsMetrics;
use Filament\Widgets\ChartWidget;

/**
 * How far readers get into articles, and how long they read.
 */
class ReadingDepthChart extends ChartWidget
{
    protected ?string $heading = 'How far readers get';

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '280px';

    public function getDescription(): string
    {
        $summary = app(AnalyticsMetrics::class)->readingSummary(30);

        if ($summary['reads'] === 0) {
            return 'Share of reads that got past each part of the article text, last 30 days. None yet (counted from 25 Nov 2026).';
        }

        $time = $summary['average_seconds'] >= 60
            ? intdiv($summary['average_seconds'], 60).' min '.($summary['average_seconds'] % 60).' s'
            : $summary['average_seconds'].' s';

        return number_format($summary['reads']).' reads, last 30 days; average active reading time '.$time.'.';
    }

    protected function getData(): array
    {
        $summary = app(AnalyticsMetrics::class)->readingSummary(30);

        return [
            'datasets' => [
                ChartStyle::bars('% of reads', [
                    $summary['reached_25'] ?? 0,
                    $summary['reached_50'] ?? 0,
                    $summary['reached_75'] ?? 0,
                    $summary['finished'] ?? 0,
                ], ChartStyle::YELLOW),
            ],
            'labels' => ['A quarter', 'Half', 'Three quarters', 'The end'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales' => ['y' => ['beginAtZero' => true, 'max' => 100, 'ticks' => ['precision' => 0]]],
        ];
    }
}
