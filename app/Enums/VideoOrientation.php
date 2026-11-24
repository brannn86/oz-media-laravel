<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Which way a social video is shot: long-form (landscape) or vertical
 * (Shorts, TikTok, Reels).
 */
enum VideoOrientation: string implements HasLabel
{
    case Landscape = 'landscape';
    case Portrait = 'portrait';

    public function getLabel(): string
    {
        return match ($this) {
            self::Landscape => 'Landscape (long-form video)',
            self::Portrait => 'Portrait (Shorts, TikTok, Reels)',
        };
    }
}
