<?php

namespace App\Providers\Filament;

use App\Filament\NavigationGroup;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\StudioMode;
use App\Http\Middleware\ServeStorageFromThisHost;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * The admin panel: admin.ozmedianet.com (dashboard at /dashboard) when
 * ADMIN_DOMAIN is set, else /admin on the same host as the site.
 */
class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $domain = config('oz.domains.admin');

        return $panel
            ->default()
            ->id('admin')
            ->domain($domain)
            ->path($domain ? '' : 'admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login()
            ->brandName('OZ Media Admin')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->navigationGroups(NavigationGroup::class)
            ->sidebarCollapsibleOnDesktop()
            ->globalSearchKeyBindings(['mod+k'])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            // The bare panel address opens the dashboard (or the login).
            // The inner closure must sit on its own line: route:cache serializes closures by
            // source line, and with two on one line it caches the outer one (returns a Route).
            ->authenticatedRoutes(function () {
                Route::get('/', fn () => redirect(Dashboard::getUrl()))->name('home');
            })
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            // Laravel Pulse: server health, queues and Reverb load (developers only).
            ->navigationItems([
                // Studio Mode lives on its own domain (studio.ozmedianet.com).
                NavigationItem::make('Studio Mode')
                    ->url(fn (): string => StudioMode::getUrl(panel: 'studio'), shouldOpenInNewTab: true)
                    ->icon(Heroicon::OutlinedComputerDesktop)
                    ->group(NavigationGroup::LiveChat)
                    ->sort(0)
                    ->visible(fn (): bool => StudioMode::canAccess()),
                NavigationItem::make('Server health (Pulse)')
                    ->url('/pulse', shouldOpenInNewTab: true)
                    ->icon(Heroicon::OutlinedHeart)
                    ->group(NavigationGroup::Access)
                    ->sort(10)
                    ->visible(fn (): bool => Gate::allows('viewPulse')),
            ])
            ->plugins([
                FilamentShieldPlugin::make()
                    // Must be the enum (not its label): mixing both splits the group and drops items.
                    ->navigationGroup(NavigationGroup::Access)
                    ->navigationSort(2),
            ])
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
            // Persistent, so Livewire requests (which load the upload previews) get it too.
            ->middleware([ServeStorageFromThisHost::class], isPersistent: true)
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
