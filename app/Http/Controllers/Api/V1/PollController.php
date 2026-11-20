<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Polls\CastPollVoteAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\PollResource;
use App\Models\Poll;
use App\Models\Station;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PollController extends Controller
{
    /**
     * The poll on air right now (or null), and your vote in it.
     */
    public function current(Request $request, Station $station, CastPollVoteAction $votes): JsonResponse
    {
        $poll = $station->polls()->live()->with('options')->latest('opened_at')->first();
        $user = $request->user('sanctum');

        return response()->json([
            'data' => $poll ? PollResource::make($poll)->resolve($request) : null,
            'my_vote' => $poll && $user instanceof User ? $votes->votedOptionId($user, $poll) : null,
            'channel' => $station->pollsChannel(),
        ]);
    }

    public function vote(Request $request, Station $station, Poll $poll, CastPollVoteAction $cast): JsonResponse
    {
        $data = $request->validate(['option_id' => ['required', 'integer']]);

        /** @var User $user */
        $user = $request->user();

        $poll = $cast->handle($user, $poll, (int) $data['option_id']);

        return PollResource::make($poll)
            ->additional([
                'my_vote' => (int) $data['option_id'],
                'points_balance' => $user->points_balance,
            ])
            ->response();
    }
}
