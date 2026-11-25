<?php

namespace App\Concerns;

use App\Enums\AgeRange;
use App\Enums\Gender;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

trait ProfileValidationRules
{
    /**
     * Get the validation rules used to validate user profiles.
     *
     * @return array<string, array<int, Unique|ValidationRule|array<mixed>|string>>
     */
    protected function profileRules(?int $userId = null): array
    {
        return [
            'name' => $this->nameRules(),
            'email' => $this->emailRules($userId),
        ];
    }

    /**
     * Get the validation rules used to validate user names.
     *
     * @return array<int, Unique|ValidationRule|array<mixed>|string>
     */
    protected function nameRules(): array
    {
        return ['required', 'string', 'max:255'];
    }

    /**
     * Get the validation rules used to validate user emails.
     *
     * @return array<int, Unique|ValidationRule|array<mixed>|string>
     */
    protected function emailRules(?int $userId = null): array
    {
        return [
            'required',
            'string',
            'email',
            'max:255',
            $userId === null
                ? Rule::unique(User::class)
                : Rule::unique(User::class)->ignore($userId),
        ];
    }

    /**
     * Age range, gender and location: asked at sign-up on the website
     * (`$required`), optional for the app and when editing a profile.
     *
     * @return array<string, array<int, ValidationRule|string|object>>
     */
    protected function listenerDetailsRules(bool $required = false): array
    {
        $presence = $required ? 'required' : 'nullable';

        return [
            'age_range' => [$presence, Rule::enum(AgeRange::class)],
            'gender' => [$presence, Rule::enum(Gender::class)],
            'location' => [$presence, 'string', 'max:100'],
        ];
    }
}
