<?php

namespace App\Filament\Pages\Analytics;

use App\Actions\Analytics\ProgramAudience;
use App\Filament\Analytics\AnalyticsPage;
use App\Filament\Analytics\Widgets\HostAudienceTable;
use App\Filament\Analytics\Widgets\ProgramAudienceTable;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

/**
 * Who listens to which show and host: audience matched to the schedule.
 */
class ProgramsAnalytics extends AnalyticsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMicrophone;

    protected static ?string $navigationLabel = 'Programs';

    protected static ?string $title = 'Programs analytics';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'analytics/programs';

    protected function exports(): array
    {
        return [
            'Per show, 30 days' => fn (): array => [
                ['Show', 'Station', 'Hosts', 'On air (h)', 'Avg. Icecast', 'Peak Icecast', 'Avg. website', 'Website hours', 'Tune-outs per hour', 'Left in first 10 min'],
                array_map(fn (array $row): array => [
                    $row['program'], $row['station'], $row['hosts'], $row['airtime_hours'], $row['avg_icecast'], $row['peak_icecast'],
                    $row['avg_website'], $row['website_hours'], $row['tune_outs_per_hour'], $row['early_tune_outs'],
                ], app(ProgramAudience::class)->programs(30)),
            ],
            'Per host, 30 days' => fn (): array => [
                ['Host', 'Shows', 'On air (h)', 'Avg. Icecast', 'Avg. website', 'Website hours'],
                array_map(fn (array $row): array => array_values($row), app(ProgramAudience::class)->hosts(30)),
            ],
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ProgramAudienceTable::class,
            HostAudienceTable::class,
        ];
    }
}
