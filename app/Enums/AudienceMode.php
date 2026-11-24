<?php

namespace App\Enums;

/**
 * What someone with the site's player on is doing.
 */
enum AudienceMode: string
{
    case Audio = 'audio';
    case Video = 'video';
}
