<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Station;
use App\Models\User;
use App\Support\PageMeta;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The signed-in listener's profile and settings, inside the station site so
 * the player keeps playing. Forms post to the shared settings endpoints.
 */
class StationProfileController extends Controller
{
    public function __invoke(Request $request, Station $station): Response
    {
        /** @var User $user */
        $user = $request->user();

        return Inertia::render('station/profile', [
            'meta' => PageMeta::make("Your profile | {$station->name}", "Your {$station->name} account."),
            'profile' => [
                'member_since' => $user->created_at?->toIso8601String(),
                'email_verified' => $user->hasVerifiedEmail(),
                'two_factor_enabled' => $user->two_factor_confirmed_at !== null,
                'age_range' => $user->age_range?->value,
                'gender' => $user->gender?->value,
                'location' => $user->location,
            ],
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
            'status' => $request->session()->get('status'),
        ]);
    }
}
