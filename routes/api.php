<?php

use App\Http\Controllers\Api\V1\AudienceController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ChatController;
use App\Http\Controllers\Api\V1\HeartbeatController;
use App\Http\Controllers\Api\V1\ListenerPresenceController;
use App\Http\Controllers\Api\V1\PollController;
use App\Http\Controllers\Api\V1\ReactionController;
use App\Http\Controllers\Api\V1\RewardController;
use App\Http\Controllers\Api\V1\SongRequestController;
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

            // OZ Poin: one heartbeat a minute while playing (extra calls earn nothing).
            Route::post('heartbeat', HeartbeatController::class)
                ->middleware(['auth:sanctum', 'throttle:10,1'])
                ->name('heartbeat');

            // Song requests (scoped: a request id from another station 404s).
            Route::scopeBindings()->group(function () {
                Route::get('requests', [SongRequestController::class, 'index'])->name('requests.index');
                Route::post('requests', [SongRequestController::class, 'store'])
                    ->middleware(['auth:sanctum', 'throttle:10,1'])
                    ->name('requests.store');
                Route::post('requests/{songRequest}/upvote', [SongRequestController::class, 'upvote'])
                    ->middleware(['auth:sanctum', 'throttle:60,1'])
                    ->name('requests.upvote');

                Route::get('polls/current', [PollController::class, 'current'])->name('polls.current');
                Route::post('polls/{poll}/votes', [PollController::class, 'vote'])
                    ->middleware(['auth:sanctum', 'throttle:20,1'])
                    ->name('polls.vote');
            });

            // Analytics: every player, guests included, once a minute while on.
            Route::post('audience', AudienceController::class)
                ->middleware('throttle:12,1')
                ->name('audience');

            Route::post('reactions', ReactionController::class)
                ->middleware('throttle:120,1')
                ->name('reactions');

            Route::get('rewards', [RewardController::class, 'index'])->name('rewards.index');
            Route::post('rewards/{reward}/redeem', [RewardController::class, 'redeem'])
                ->middleware(['auth:sanctum', 'throttle:10,1'])
                ->name('rewards.redeem');
            Route::get('leaderboard', [RewardController::class, 'leaderboard'])->name('leaderboard');
        });

    Route::middleware('auth:sanctum')->prefix('me')->name('me.')->group(function () {
        Route::get('points', [RewardController::class, 'myPoints'])->name('points');
        Route::get('redemptions', [RewardController::class, 'myRedemptions'])->name('redemptions');
    });
});
