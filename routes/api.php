<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ChatController;
use App\Http\Controllers\Api\V1\ListenerPresenceController;
use App\Http\Controllers\Api\V1\StationController;
use App\Http\Middleware\EnsureStationIsActive;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1: shared by the website and the mobile app
|--------------------------------------------------------------------------
|
| Auth is Sanctum: the website calls these with its session cookie
| (stateful, same domain), the app with a Bearer token from /auth/login.
|
*/

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::prefix('auth')->name('auth.')->middleware('throttle:10,1')->group(function () {
        Route::post('register', [AuthController::class, 'register'])->name('register');
        Route::post('login', [AuthController::class, 'login'])->name('login');
        Route::post('google', [AuthController::class, 'google'])->name('google');
        Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum')->name('logout');
    });

    Route::get('me', [AuthController::class, 'me'])->middleware('auth:sanctum')->name('me');

    Route::prefix('stations/{station:slug}')
        ->middleware(EnsureStationIsActive::class)
        ->name('stations.')
        ->group(function () {
            Route::get('/', [StationController::class, 'show'])->name('show');
            Route::get('now-playing', [StationController::class, 'nowPlaying'])->name('now-playing');

            Route::get('chat', [ChatController::class, 'index'])->name('chat.index');
            Route::post('chat/messages', [ChatController::class, 'store'])
                ->middleware(['auth:sanctum', 'throttle:30,1'])
                ->name('chat.store');

            Route::post('listeners/auth', ListenerPresenceController::class)
                ->middleware('throttle:30,1')
                ->name('listeners.auth');
        });
});
