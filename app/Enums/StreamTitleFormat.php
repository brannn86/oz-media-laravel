<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Which side of the " - " in an Icecast stream title is the song.
 */
enum StreamTitleFormat: string implements HasLabel
{
    case TitleArtist = 'title_artist';
    case ArtistTitle = 'artist_title';

    public function getLabel(): string
    {
        return match ($this) {
            self::TitleArtist => 'Title - Artist',
            self::ArtistTitle => 'Artist - Title',
        };
    }

    /**
     * Splits "A - B" into [title, artist]; a title without a separator has no artist.
     *
     * @return array{0: string, 1: string|null}
     */
    public function parse(string $raw): array
    {
        $parts = preg_split('/\s+[-–—]\s+/u', trim($raw), 2);

        if ($parts === false || count($parts) < 2 || trim($parts[0]) === '' || trim($parts[1]) === '') {
            return [trim($raw), null];
        }

        [$first, $second] = array_map('trim', $parts);

        return $this === self::TitleArtist ? [$first, $second] : [$second, $first];
    }
}
