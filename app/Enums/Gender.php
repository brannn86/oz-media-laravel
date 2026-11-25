<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * A listener's gender, asked at sign-up.
 */
enum Gender: string implements HasLabel
{
    case Female = 'female';
    case Male = 'male';
    case PreferNotToSay = 'prefer_not_to_say';

    public function getLabel(): string
    {
        return match ($this) {
            self::Female => 'Female',
            self::Male => 'Male',
            self::PreferNotToSay => 'Prefer not to say',
        };
    }
}
