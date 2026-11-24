<?php

namespace App\Filament\Analytics\Widgets;

/**
 * OZ colours and shared Chart.js settings for the Analytics charts.
 */
final class ChartStyle
{
    public const YELLOW = '#f2bd51';

    public const NAVY = '#141f43';

    public const RED = '#d8281b';

    public const INK = '#3a3a3a';

    /**
     * @param  list<int|float>  $data
     * @return array<string, mixed>
     */
    public static function line(string $label, array $data, string $color, bool $fill = false): array
    {
        return [
            'label' => $label,
            'data' => $data,
            'borderColor' => $color,
            'backgroundColor' => self::alpha($color, $fill ? 0.15 : 0.05),
            'fill' => $fill,
            'tension' => 0.3,
            'pointRadius' => 2,
        ];
    }

    /**
     * @param  list<int|float>  $data
     * @return array<string, mixed>
     */
    public static function bars(string $label, array $data, string $color): array
    {
        return [
            'label' => $label,
            'data' => $data,
            'backgroundColor' => $color,
            'borderRadius' => 4,
        ];
    }

    /**
     * Counts of people or things: from zero, whole numbers.
     *
     * @return array<string, mixed>
     */
    public static function countAxes(bool $stacked = false): array
    {
        return [
            'scales' => [
                'x' => ['stacked' => $stacked],
                'y' => ['beginAtZero' => true, 'suggestedMax' => 5, 'ticks' => ['precision' => 0], 'stacked' => $stacked],
            ],
        ];
    }

    private static function alpha(string $hex, float $alpha): string
    {
        [$r, $g, $b] = array_map(hexdec(...), str_split(ltrim($hex, '#'), 2));

        return "rgba({$r}, {$g}, {$b}, {$alpha})";
    }
}
