<?php

namespace App\Providers\Filament;

use App\Filament\Pages\StudioMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * The studio view for the people on air: Studio Mode on its own, full
 * width, at studio.ozmedianet.com when STUDIO_DOMAIN is set, else /studio.
 * Same accounts and permissions as the admin panel.
 */
class StudioPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $domain = config('oz.domains.studio');

        return $panel
            ->id('studio')
            ->domain($domain)
            ->path($domain ? '' : 'studio')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login()
            ->brandName('OZ Studio')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->navigation(false)
            ->maxContentWidth('full')
            ->pages([
                StudioMode::class,
            ])
            ->authenticatedRoutes(fn () => Route::get('/', fn () => redirect(StudioMode::getUrl()))->name('home'))
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
