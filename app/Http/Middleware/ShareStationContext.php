<?php

namespace App\Http\Middleware;

use App\Actions\Broadcast\GetCurrentBroadcastAction;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Resources\AccountResource;
use App\Http\Resources\BroadcastResource;
use App\Http\Resources\ChatSettingsResource;
use App\Http\Resources\StationResource;
use App\Models\Station;
use App\Models\User;
use App\Support\StationCache;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shares the current station and its on-air status with every page under /{station},
 * so StationLayout (and its persistent player) always has them.
 *
 * Both are lazy closures: the player refreshes them with a partial reload
 * (`only: ['station', 'broadcast']`) without re-rendering the page.
 */
class ShareStationContext
{
    public function __construct(private readonly GetCurrentBroadcastAction $getCurrentBroadcast) {}

    public function handle(Request $request, Closure $next): Response
    {
        $station = $request->route('station');

        abort_unless($station instanceof Station && $station->is_active, 404);

        Inertia::share([
            // Station settings rarely change; cached until they do (StationContentObserver).
            'station' => fn () => StationCache::remember($station, 'station-resource', fn (): array => StationResource::make($station)->resolve($request)),
            'broadcast' => fn () => BroadcastResource::make($this->getCurrentBroadcast->handle($station))->resolve($request),
            'chat' => fn () => [
                'settings' => ChatSettingsResource::make($station)->resolve($request),
                'google_sign_in' => GoogleAuthController::isConfigured(),
            ],
            // The signed-in listener (points, streak), or null for guests.
            'account' => fn () => $request->user() instanceof User
                ? AccountResource::make($request->user())->resolve($request)
                : null,
        ]);

        return $next($request);
    }
}
