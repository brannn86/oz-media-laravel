<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Mobile sign-in: checks credentials and returns a Sanctum personal access token.
 */
class IssueApiTokenAction
{
    private const MAX_ATTEMPTS = 5;

    /**
     * @throws ValidationException on bad credentials or too many attempts
     */
    public function withPassword(string $email, string $password, string $deviceName, string $ip): IssuedToken
    {
        $key = 'api-login:'.mb_strtolower($email).'|'.$ip;

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'email' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($key), 'minutes' => 1]),
            ]);
        }

        $user = User::query()->where('email', $email)->first();

        if (! $user || $user->password === null || ! Auth::getProvider()->validateCredentials($user, ['password' => $password])) {
            RateLimiter::hit($key);

            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        RateLimiter::clear($key);

        return $this->for($user, $deviceName);
    }

    public function for(User $user, string $deviceName): IssuedToken
    {
        return new IssuedToken(
            $user,
            $user->createToken(mb_substr($deviceName, 0, 100), ['listener'])->plainTextToken,
        );
    }
}
