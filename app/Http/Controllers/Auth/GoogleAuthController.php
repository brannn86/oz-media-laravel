<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\ResolveGoogleListenerAction;
use App\Http\Controllers\Controller;
use App\Support\ReturnPath;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Throwable;

/**
 * "Continue with Google" on the website (session login).
 */
class GoogleAuthController extends Controller
{
    public function redirect(Request $request): SymfonyRedirect
    {
        abort_unless(self::isConfigured(), 404);

        ReturnPath::remember($request);

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request, ResolveGoogleListenerAction $resolve): RedirectResponse
    {
        abort_unless(self::isConfigured(), 404);

        try {
            $user = $resolve->handle(Socialite::driver('google')->user());
        } catch (ValidationException $e) {
            return to_route('login')->withErrors($e->errors());
        } catch (Throwable $e) {
            report($e);

            return to_route('login')->withErrors(['email' => 'Google sign-in did not complete. Please try again.']);
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(config('fortify.home'));
    }

    public static function isConfigured(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }
}
