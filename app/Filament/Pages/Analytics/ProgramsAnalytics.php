<?php

namespace App\Filament\Pages\Analytics;

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

    protected function getHeaderWidgets(): array
    {
        return [
            ProgramAudienceTable::class,
            HostAudienceTable::class,
        ];
    }
}
