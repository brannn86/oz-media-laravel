<?php

namespace App\Filament\Analytics\Widgets;

use App\Actions\Analytics\AnalyticsMetrics;
use App\Enums\ReactionType;
use Filament\Widgets\ChartWidget;

class ReactionsChart extends ChartWidget
{
    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Live reactions per day';

    protected ?string $description = 'Fire, clap and heart sent over the video livestream, last 14 days.';

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $series = app(AnalyticsMetrics::class)->reactionSeries(14);
        $colors = [
            ReactionType::Fire->value => ChartStyle::RED,
            ReactionType::Clap->value => ChartStyle::YELLOW,
            ReactionType::Heart->value => ChartStyle::NAVY,
        ];

        return [
            'datasets' => array_map(fn (ReactionType $type): array => ChartStyle::bars(
                ucfirst($type->value),
                array_map(fn (array $day): int => (int) ($day[$type->value] ?? 0), $series),
                $colors[$type->value],
            ), ReactionType::cases()),
            'labels' => app(AnalyticsMetrics::class)->dayLabels(14),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return ChartStyle::countAxes(stacked: true);
    }
}
