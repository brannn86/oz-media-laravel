<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * A listener's age, asked as a range at sign-up.
 */
enum AgeRange: string implements HasLabel
{
    case Under18 = 'under_18';
    case From18To24 = '18_24';
    case From25To34 = '25_34';
    case From35To44 = '35_44';
    case From45To54 = '45_54';
    case From55 = '55_plus';
    case PreferNotToSay = 'prefer_not_to_say';

    public function getLabel(): string
    {
        return match ($this) {
            self::Under18 => 'Under 18',
            self::From18To24 => '18–24',
            self::From25To34 => '25–34',
            self::From35To44 => '35–44',
            self::From45To54 => '45–54',
            self::From55 => '55 or older',
            self::PreferNotToSay => 'Prefer not to say',
        };
    }
}
