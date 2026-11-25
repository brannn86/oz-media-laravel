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
     * @param  array<string, mixed>  $input  name, email, password, password_confirmation, age_range, gender, location
     * @param  bool  $requireDetails  The website asks for age range, gender and location; the app may not (yet).
     *
     * @throws ValidationException
     */
    public function handle(array $input, bool $dispatchRegistered = true, bool $requireDetails = false): User
    {
        $data = Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            ...$this->listenerDetailsRules($requireDetails),
        ], attributes: ['age_range' => 'age range'])->validate();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'age_range' => $data['age_range'] ?? null,
            'gender' => $data['gender'] ?? null,
            'location' => isset($data['location']) ? trim($data['location']) : null,
        ]);

        if ($dispatchRegistered) {
            event(new Registered($user));
        }

        return $user;
    }
}
