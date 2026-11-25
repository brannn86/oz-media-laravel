<?php

namespace App\Filament\Pages\Analytics;

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
