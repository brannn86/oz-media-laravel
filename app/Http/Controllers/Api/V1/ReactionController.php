<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Live\SendReactionAction;
use App\Enums\ReactionType;
use App\Http\Controllers\Controller;
use App\Models\Station;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

/**
 * Anyone watching can react; accounts are throttled by id, guests by
 * session (web) or X-Listener-Id (app), falling back to IP.
 */
class ReactionController extends Controller
{
    public function __invoke(Request $request, Station $station, SendReactionAction $send): Response
    {
        $data = $request->validate(['type' => ['required', Rule::enum(ReactionType::class)]]);
        $user = $request->user('sanctum');

        $viewer = match (true) {
            $user instanceof User => "user:{$user->id}",
            $request->hasHeader('X-Listener-Id') => 'device:'.$request->header('X-Listener-Id'),
            $request->hasSession() => 'session:'.$request->session()->getId(),
            default => 'ip:'.$request->ip(),
        };

        $send->handle($station, ReactionType::from($data['type']), $viewer);

        return response()->noContent();
    }
}
