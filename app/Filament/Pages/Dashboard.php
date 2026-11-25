<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

/**
 * The admin home, at /dashboard (admin.ozmedianet.com/dashboard); the bare
 * panel address redirects here.
 */
class Dashboard extends BaseDashboard
{
    protected static string $routePath = 'dashboard';
}
