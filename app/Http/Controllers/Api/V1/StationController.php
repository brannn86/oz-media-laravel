<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Broadcast\GetCurrentBroadcastAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\BroadcastResource;
use App\Http\Resources\ChatSettingsResource;
use App\Http\Resources\StationResource;
use App\Models\Station;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Station info for the mobile app: the same resources the website shares via Inertia.
 */
class StationController extends Controller
{
    public function show(Request $request, Station $station, GetCurrentBroadcastAction $getCurrentBroadcast): JsonResponse
    {
        return response()->json([
            'data' => [
                ...StationResource::make($station)->resolve($request),
                'chat' => ChatSettingsResource::make($station)->resolve($request),
            ],
            'broadcast' => BroadcastResource::make($getCurrentBroadcast->handle($station))->resolve($request),
        ]);
    }

    public function nowPlaying(Request $request, Station $station, GetCurrentBroadcastAction $getCurrentBroadcast): BroadcastResource
    {
        return BroadcastResource::make($getCurrentBroadcast->handle($station));
    }
}
