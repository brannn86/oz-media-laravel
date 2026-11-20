<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Points\GetLeaderboardAction;
use App\Actions\Rewards\RedeemRewardAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\PointTransactionResource;
use App\Http\Resources\RedemptionResource;
use App\Http\Resources\RewardResource;
use App\Models\Reward;
use App\Models\Station;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

/**
 * OZ Poin: rewards catalogue, redeeming, leaderboard and your own history.
 */
class RewardController extends Controller
{
    public function index(Request $request, Station $station): AnonymousResourceCollection
    {
        return RewardResource::collection(Reward::query()->availableOn($station)->get());
    }

    public function redeem(Request $request, Station $station, Reward $reward, RedeemRewardAction $redeem): JsonResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:500']]);

        /** @var User $user */
        $user = $request->user();

        $redemption = $redeem->handle($user, $reward, $station, $data['note'] ?? null);

        return RedemptionResource::make($redemption->load('reward'))
            ->additional(['points_balance' => $user->points_balance])
            ->response()
            ->setStatusCode(201);
    }

    public function leaderboard(Request $request, Station $station, GetLeaderboardAction $leaderboard): JsonResponse
    {
        $data = $request->validate(['period' => ['nullable', Rule::in(GetLeaderboardAction::PERIODS)]]);

        return response()->json([
            'period' => $data['period'] ?? 'week',
            'data' => $leaderboard->handle($station, $data['period'] ?? 'week'),
        ]);
    }

    public function myRedemptions(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        return RedemptionResource::collection($user->redemptions()->with('reward')->latest()->limit(50)->get());
    }

    public function myPoints(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        return PointTransactionResource::collection($user->pointTransactions()->with('station:id,name')->latest('id')->paginate(30))
            ->additional(['points_balance' => $user->points_balance, 'current_streak_days' => $user->current_streak_days]);
    }
}
