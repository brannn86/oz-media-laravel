<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\SongRequests\SubmitSongRequestAction;
use App\Actions\SongRequests\ToggleSongRequestUpvoteAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\SongRequestResource;
use App\Models\SongRequest;
use App\Models\Station;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SongRequestController extends Controller
{
    /**
     * The live queue, plus which requests you have upvoted.
     */
    public function index(Request $request, Station $station): JsonResponse
    {
        $queue = $station->songRequests()->queue()->with('user.roles')->limit(50)->get();
        $user = $request->user('sanctum');

        $upvoted = $user instanceof User
            ? DB::table('song_request_upvotes')
                ->where('user_id', $user->id)
                ->whereIn('song_request_id', $queue->modelKeys())
                ->pluck('song_request_id')
                ->map(fn ($id): int => (int) $id)
                ->values()
            : collect();

        return response()->json([
            'data' => SongRequestResource::collection($queue)->resolve($request),
            'my_upvotes' => $upvoted,
            'channel' => $station->requestsChannel(),
            'limits' => [
                'max_open_per_listener' => (int) config('oz.requests.max_open_per_listener'),
                'cooldown_seconds' => (int) config('oz.requests.cooldown_seconds'),
            ],
        ]);
    }

    public function store(Request $request, Station $station, SubmitSongRequestAction $submit): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $songRequest = $submit->handle($user, $station, $request->only(['song_title', 'artist', 'shoutout_message']));

        return SongRequestResource::make($songRequest)->response()->setStatusCode(201);
    }

    public function upvote(Request $request, Station $station, SongRequest $songRequest, ToggleSongRequestUpvoteAction $toggle): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $upvoted = $toggle->handle($user, $songRequest);

        return SongRequestResource::make($songRequest->load('user'))
            ->additional(['upvoted' => $upvoted])
            ->response();
    }
}
