<?php

namespace App\Filament\Pages\Analytics;

use App\Filament\Analytics\AnalyticsPage;
use App\Filament\Analytics\Widgets\RadioListenersChart;
use App\Filament\Analytics\Widgets\RadioStats;
use App\Filament\Analytics\Widgets\TopSongsTable;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

/**
 * The radio stream: Icecast and website listeners, listening time, songs and requests.
 */
class RadioAnalytics extends AnalyticsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRadio;

    protected static ?string $navigationLabel = 'Radio';

    protected static ?string $title = 'Radio analytics';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'analytics/radio';

    protected function getHeaderWidgets(): array
    {
        return [
            RadioStats::class,
            RadioListenersChart::class,
            TopSongsTable::class,
        ];
    }
}
