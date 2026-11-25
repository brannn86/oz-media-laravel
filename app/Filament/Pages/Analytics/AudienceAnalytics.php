<?php

namespace App\Filament\Pages\Analytics;

use App\Actions\Analytics\AcquisitionMetrics;
use App\Actions\Analytics\AnalyticsMetrics;
use App\Filament\Analytics\AnalyticsPage;
use App\Filament\Analytics\Widgets\AudienceChart;
use App\Filament\Analytics\Widgets\AudienceStats;
use App\Filament\Analytics\Widgets\CampaignsTable;
use App\Filament\Analytics\Widgets\CohortTable;
use App\Filament\Analytics\Widgets\RetentionStats;
use App\Filament\Analytics\Widgets\SourcesTable;
use App\Filament\Analytics\Widgets\TopListenersTable;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

/**
 * Who listens: accounts and sign-ups, where listeners come from, whether they come back, the most loyal listeners.
 */
class AudienceAnalytics extends AnalyticsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Audience';

    protected static ?string $title = 'Audience analytics';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'analytics/audience';

    protected function exports(): array
    {
        return [
            'Daily figures, 30 days' => function (): array {
                $metrics = app(AnalyticsMetrics::class);
                $accounts = $metrics->audienceSeries(30);
                $players = $metrics->playerSeries(30);

                return [
                    ['Date', 'New sign-ups', 'Active signed-in listeners', 'Radio listeners on the site', 'Video viewers on the site'],
                    array_map(fn (string $date, array $account, array $player): array => [
                        $date, $account['signups'], $account['active'], $player['audio_people'], $player['video_people'],
                    ], $metrics->dates(30), $accounts, $players),
                ];
            },
            'Where listeners come from, 30 days' => fn (): array => [
                ['Source', 'Sessions', 'Listeners', 'Hours', 'Typical session (min)'],
                array_map(fn (array $row): array => array_values($row), app(AcquisitionMetrics::class)->sources(30)),
            ],
            'Campaigns, 30 days' => fn (): array => [
                ['Campaign', 'Source', 'Medium', 'Sessions', 'Listeners', 'Hours'],
                array_map(fn (array $row): array => array_values($row), app(AcquisitionMetrics::class)->campaigns(30)),
            ],
            'Weekly cohorts' => fn (): array => [
                ['Week', 'New listeners', 'Back in week 1 (%)', 'Back in week 2 (%)', 'Back in week 3 (%)', 'Back in week 4 (%)', 'Sign-ups', 'Sign-ups back next week (%)'],
                array_map(fn (array $row): array => array_values($row), app(AcquisitionMetrics::class)->weeklyCohorts(8)),
            ],
            'Most loyal listeners, 30 days' => fn (): array => [
                ['Name', 'Email', 'Listening minutes', 'Streak (days)', 'OZ Poin'],
                array_map(fn (array $row): array => array_values($row), app(AnalyticsMetrics::class)->topListeners(30)),
            ],
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AudienceStats::class,
            AudienceChart::class,
            SourcesTable::class,
            CampaignsTable::class,
            RetentionStats::class,
            CohortTable::class,
            TopListenersTable::class,
        ];
    }
}
