<?php

namespace App\Filament\Pages\Analytics;

use App\Actions\Analytics\DeviceMetrics;
use App\Filament\Analytics\AnalyticsPage;
use App\Filament\Analytics\Widgets\DeviceBreakdownChart;
use App\Filament\Analytics\Widgets\IcecastPlayersChart;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

/**
 * What listeners use: phone or desktop, website or app, OS and browser (from
 * sessions on the site and app), and every Icecast player (smart speakers,
 * radio apps, ...) when the Icecast admin login is set.
 */
class DevicesAnalytics extends AnalyticsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDevicePhoneMobile;

    protected static ?string $navigationLabel = 'Devices';

    protected static ?string $title = 'Devices analytics';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'analytics/devices';

    protected function exports(): array
    {
        $exports = [];

        foreach (DeviceMetrics::DIMENSIONS as $dimension => $label) {
            $exports["{$label}, 30 days"] = fn (): array => [
                [$label, 'Sessions', 'People'],
                array_map(fn (array $row): array => array_values($row), app(DeviceMetrics::class)->breakdown($dimension, 30)),
            ];
        }

        $exports['Icecast players, 7 days'] = fn (): array => [
            ['Player', 'Average listeners', 'Highest'],
            array_map(fn (array $row): array => array_values($row), app(DeviceMetrics::class)->icecastPlayers(7)),
        ];

        return $exports;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            IcecastPlayersChart::class,
            DeviceBreakdownChart::make(['dimension' => 'device']),
            DeviceBreakdownChart::make(['dimension' => 'platform']),
            DeviceBreakdownChart::make(['dimension' => 'os']),
            DeviceBreakdownChart::make(['dimension' => 'browser']),
        ];
    }
}
