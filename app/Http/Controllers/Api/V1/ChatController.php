<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Chat\GetChatHistoryAction;
use App\Actions\Chat\SendChatMessageAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\AccountResource;
use App\Http\Resources\ChatMessageResource;
use App\Http\Resources\ChatSettingsResource;
use App\Models\Station;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Live chat for the website (Sanctum session) and the mobile app (Sanctum token).
 */
class ChatController extends Controller
{
    /**
     * Recent history, the pinned message, settings and (if signed in) who you are.
     */
    public function index(Request $request, Station $station, GetChatHistoryAction $history): JsonResponse
    {
        ['messages' => $messages, 'pinned' => $pinned] = $history->handle($station);
        $user = $request->user('sanctum');

        return response()->json([
            'data' => ChatMessageResource::collection($messages)->resolve($request),
            'pinned' => $pinned ? ChatMessageResource::make($pinned)->resolve($request) : null,
            'settings' => ChatSettingsResource::make($station)->resolve($request),
            'me' => $user instanceof User ? AccountResource::make($user)->resolve($request) : null,
        ]);
    }

    public function store(Request $request, Station $station, SendChatMessageAction $send): JsonResponse
    {
        $request->validate(['body' => ['required', 'string']]);

        /** @var User $user */
        $user = $request->user();

        $message = $send->handle($user, $station, $request->string('body')->toString());

        return ChatMessageResource::make($message)
            ->additional(['cooldown_seconds' => $send->cooldownSeconds($user, $station)])
            ->response()
            ->setStatusCode(201);
    }
}
