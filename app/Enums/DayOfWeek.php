<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Matches Carbon's dayOfWeek numbering (0 = Sunday).
 */
enum DayOfWeek: int implements HasLabel
{
    case Sunday = 0;
    case Monday = 1;
    case Tuesday = 2;
    case Wednesday = 3;
    case Thursday = 4;
    case Friday = 5;
    case Saturday = 6;

    public function getLabel(): string
    {
        return $this->name;
    }

    /**
     * Indonesian day name, used on the public site.
     */
    public function localLabel(): string
    {
        return match ($this) {
            self::Sunday => 'Minggu',
            self::Monday => 'Senin',
            self::Tuesday => 'Selasa',
            self::Wednesday => 'Rabu',
            self::Thursday => 'Kamis',
            self::Friday => 'Jumat',
            self::Saturday => 'Sabtu',
        };
    }

    /**
     * @return list<self>
     */
    public static function weekdays(): array
    {
        return [self::Monday, self::Tuesday, self::Wednesday, self::Thursday, self::Friday];
    }
}
