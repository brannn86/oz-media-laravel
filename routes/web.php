<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Public\CorporateController;
use App\Http\Controllers\Public\StationController;
use App\Http\Controllers\Public\StationNewsController;
use App\Http\Controllers\Public\StationProfileController;
use App\Http\Middleware\ShareStationContext;
use Illuminate\Support\Facades\Route;

// The public site, pinned to the main domain when MAIN_DOMAIN is set (so it
// doesn't also answer on the admin and studio domains); any host otherwise.
Route::domain(config('oz.domains.main'))->group(function () {
    Route::get('/', CorporateController::class)->name('home');

    require __DIR__.'/settings.php';

    // Listener "Continue with Google" (session login; the app uses POST /api/v1/auth/google).
    Route::middleware('guest')->prefix('auth/google')->name('auth.google.')->group(function () {
        Route::get('redirect', [GoogleAuthController::class, 'redirect'])->name('redirect');
        Route::get('callback', [GoogleAuthController::class, 'callback'])->name('callback');
    });

    /*
    |--------------------------------------------------------------------------
    | Station sites: /{station}/...
    |--------------------------------------------------------------------------
    |
    | Registered last so fixed paths (admin, login, dashboard, ...) always win.
    | Every page here renders inside StationLayout with the persistent player.
    |
    */

    Route::prefix('{station:slug}')
        ->where(['station' => '[a-z0-9]+(?:-[a-z0-9]+)+'])
        ->middleware(ShareStationContext::class)
        ->name('station.')
        ->group(function () {
            Route::get('/', [StationController::class, 'home'])->name('home');
            Route::get('programs', [StationController::class, 'programs'])->name('programs');
            Route::get('announcers', [StationController::class, 'announcers'])->name('announcers');
            Route::get('events', [StationController::class, 'events'])->name('events');
            Route::get('events/{slug}', [StationController::class, 'event'])->name('events.show');
            Route::get('rewards', [StationController::class, 'rewards'])->name('rewards');
            Route::get('news', [StationNewsController::class, 'index'])->name('news.index');
            Route::get('news/{slug}', [StationNewsController::class, 'show'])->name('news.show');
            Route::get('about', [StationController::class, 'about'])->name('about');
            Route::get('privacy', [StationController::class, 'privacy'])->name('privacy');
            Route::get('profile', StationProfileController::class)->middleware('auth')->name('profile');
        });
});
