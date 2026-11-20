<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Points\RecordListeningHeartbeatAction;
use App\Http\Controllers\Controller;
use App\Models\Station;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Sent every 60 s while the player is actually playing (web and app).
 */
class HeartbeatController extends Controller
{
    public function __invoke(Request $request, Station $station, RecordListeningHeartbeatAction $heartbeat): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $result = $heartbeat->handle($user, $station);

        return response()->json([
            'accepted' => $result->accepted,
            'awarded' => $result->awarded(),
            'listening_points' => $result->listeningPoints,
            'streak_bonus' => $result->streakBonus,
            'streak_days' => $result->streakDays,
            'points_balance' => $result->balance,
            'daily_cap_reached' => $result->dailyCapReached,
            'next_in_seconds' => $result->nextInSeconds,
        ]);
    }
}
