<?php

namespace App\Filament\Analytics;

use App\Filament\NavigationGroup;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use UnitEnum;

/**
 * An Analytics page: a grid of read-only widgets, network-wide, WIB days.
 *
 * Its widgets live in App\Filament\Analytics\Widgets, outside the
 * dashboard's auto-discovered folder, so they only show here.
 */
abstract class AnalyticsPage extends Page
{
    use HasPageShield;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Analytics;

    public function getHeaderWidgetsColumns(): int|array
    {
        return ['md' => 2];
    }
}
