<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Broadcast\ListenerPresence;
use App\Http\Controllers\Controller;
use App\Models\Station;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Presence auth for the listener-count channel. Guests are allowed: you
 * don't need an account to listen. Web guests are keyed by session, app
 * guests by the X-Listener-Id header (a random id the app generates once).
 */
class ListenerPresenceController extends Controller
{
    public function __invoke(Request $request, Station $station, ListenerPresence $presence): JsonResponse
    {
        $data = $request->validate([
            'socket_id' => ['required', 'string', 'regex:/^\d+\.\d+$/'],
            'channel_name' => ['required', 'string'],
        ]);

        abort_unless($data['channel_name'] === $presence->presenceChannel($station), 403);

        $user = $request->user('sanctum');

        $guestKey = $request->header('X-Listener-Id')
            ?? ($request->hasSession() ? $request->session()->getId() : null)
            ?? Str::uuid()->toString();

        return response()->json($presence->authorize(
            $station,
            $data['channel_name'],
            $data['socket_id'],
            $user instanceof User ? $user : null,
            (string) $guestKey,
        ));
    }
}
