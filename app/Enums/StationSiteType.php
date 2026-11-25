<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * How much of the public site a station gets.
 */
enum StationSiteType: string implements HasLabel
{
    /** Home, programs, announcers, events, news, chat, video, OZ Poin. */
    case Full = 'full';

    /** One page with the radio player (and the privacy policy). */
    case Basic = 'basic';

    public function getLabel(): string
    {
        return match ($this) {
            self::Full => 'Full station site',
            self::Basic => 'Basic: radio player only',
        };
    }
}
