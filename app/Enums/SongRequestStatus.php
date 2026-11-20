<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SongRequestStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Played = 'played';
    case Rejected = 'rejected';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Waiting',
            self::Approved => 'Coming up',
            self::Played => 'Played',
            self::Rejected => 'Not this time',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Approved => 'success',
            self::Played => 'info',
            self::Rejected => 'danger',
        };
    }

    /**
     * Still in the queue listeners see (and can upvote).
     */
    public function isOpen(): bool
    {
        return $this === self::Pending || $this === self::Approved;
    }

    /**
     * @return list<string>
     */
    public static function openValues(): array
    {
        return [self::Pending->value, self::Approved->value];
    }
}
