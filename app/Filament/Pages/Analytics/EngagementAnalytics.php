<?php

namespace App\Filament\Pages\Analytics;

use App\Actions\Analytics\AnalyticsMetrics;
use App\Actions\Analytics\RequestMetrics;
use App\Filament\Analytics\AnalyticsPage;
use App\Filament\Analytics\Widgets\EngagementChart;
use App\Filament\Analytics\Widgets\EngagementStats;
use App\Filament\Analytics\Widgets\MostRequestedTable;
use App\Filament\Analytics\Widgets\RequestsChart;
use App\Filament\Analytics\Widgets\RequestStats;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

/**
 * Taking part: chat, polls, song requests and OZ Poin.
 */
class EngagementAnalytics extends AnalyticsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'Engagement';

    protected static ?string $title = 'Engagement analytics';

    protected static ?int $navigationSort = 7;

    protected static ?string $slug = 'analytics/engagement';

    protected function exports(): array
    {
        return [
            'Daily figures, 30 days' => function (): array {
                $metrics = app(AnalyticsMetrics::class);

                return [
                    ['Date', 'Chat messages', 'Song requests', 'Poll votes'],
                    array_map(fn (string $date, array $day): array => [$date, $day['chat'], $day['requests'], $day['votes']], $metrics->dates(30), $metrics->engagementSeries(30)),
                ];
            },
            'Song requests per day, 30 days' => function (): array {
                $metrics = app(AnalyticsMetrics::class);

                return [
                    ['Date', 'Requested', 'Played'],
                    array_map(fn (string $date, array $day): array => [$date, $day['requested'], $day['played']], $metrics->dates(30), app(RequestMetrics::class)->daily(30)),
                ];
            },
            'Most requested songs, 30 days' => fn (): array => [
                ['Song', 'Artist', 'Requests', 'Listeners', 'Upvotes', 'Played'],
                array_map(fn (array $row): array => array_values($row), app(RequestMetrics::class)->mostRequested(30, PHP_INT_MAX)),
            ],
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            EngagementStats::class,
            EngagementChart::class,
            RequestStats::class,
            RequestsChart::class,
            MostRequestedTable::class,
        ];
    }
}
