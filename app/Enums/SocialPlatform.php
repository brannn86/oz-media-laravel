<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SocialPlatform: string implements HasColor, HasLabel
{
    case YouTube = 'youtube';
    case TikTok = 'tiktok';
    case Instagram = 'instagram';

    public function getLabel(): string
    {
        return match ($this) {
            self::YouTube => 'YouTube',
            self::TikTok => 'TikTok',
            self::Instagram => 'Instagram',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::YouTube => 'danger',
            self::TikTok => 'gray',
            self::Instagram => 'warning',
        };
    }

    /**
     * Host names a post URL for this platform is expected to live on.
     *
     * @return list<string>
     */
    public function hosts(): array
    {
        return match ($this) {
            self::YouTube => ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be'],
            self::TikTok => ['tiktok.com', 'www.tiktok.com', 'vm.tiktok.com'],
            self::Instagram => ['instagram.com', 'www.instagram.com'],
        };
    }

    public static function detectFromUrl(?string $url): ?self
    {
        $host = strtolower((string) parse_url((string) $url, PHP_URL_HOST));

        foreach (self::cases() as $platform) {
            if (in_array($host, $platform->hosts(), true)) {
                return $platform;
            }
        }

        return null;
    }
}
