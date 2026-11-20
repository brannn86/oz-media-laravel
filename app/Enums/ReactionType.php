<?php

namespace App\Enums;

enum ReactionType: string
{
    case Fire = 'fire';
    case Clap = 'clap';
    case Heart = 'heart';

    public function emoji(): string
    {
        return match ($this) {
            self::Fire => '🔥',
            self::Clap => '👏',
            self::Heart => '❤️',
        };
    }
}
