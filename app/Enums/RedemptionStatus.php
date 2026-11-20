<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum RedemptionStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Fulfilled = 'fulfilled';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Being prepared',
            self::Fulfilled => 'Fulfilled',
            self::Cancelled => 'Cancelled (points refunded)',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Fulfilled => 'success',
            self::Cancelled => 'gray',
        };
    }
}
