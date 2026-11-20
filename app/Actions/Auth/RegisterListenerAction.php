<?php

namespace App\Actions\Auth;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Creates a listener account with email + password.
 *
 * Shared by the web sign-up (Fortify's CreateNewUser) and POST /api/v1/auth/register.
 * Listeners get no role, so they can never enter the admin panel.
 */
class RegisterListenerAction
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * @param  array<string, mixed>  $input  name, email, password, password_confirmation
     *
     * @throws ValidationException
     */
    public function handle(array $input, bool $dispatchRegistered = true): User
    {
        $data = Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        if ($dispatchRegistered) {
            event(new Registered($user));
        }

        return $user;
    }
}
