<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PollType: string implements HasLabel
{
    case General = 'general';
    case SongBattle = 'song_battle';
    case Trivia = 'trivia';

    public function getLabel(): string
    {
        return match ($this) {
            self::General => 'Poll',
            self::SongBattle => 'Song battle',
            self::Trivia => 'Trivia',
        };
    }
}
