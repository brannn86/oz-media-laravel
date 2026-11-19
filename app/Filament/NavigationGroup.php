<?php

namespace App\Filament;

use Filament\Support\Contracts\HasLabel;

/**
 * Sidebar groups, in display order.
 */
enum NavigationGroup: string implements HasLabel
{
    case Programming = 'programming';
    case News = 'news';
    case SocialAndEvents = 'social_events';
    case Station = 'station';
    case Access = 'access';

    public function getLabel(): string
    {
        return match ($this) {
            self::Programming => 'Programming',
            self::News => 'OZ News',
            self::SocialAndEvents => 'Social & Events',
            self::Station => 'Station',
            self::Access => 'Access Control',
        };
    }
}
