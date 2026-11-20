<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Str;

/**
 * The only roles allowed into the admin panel.
 *
 * `dev` is Shield's super admin role and bypasses every gate check.
 */
enum AdminRole: string implements HasColor, HasLabel
{
    case Dev = 'dev';
    case Editorial = 'editorial';
    case SocialMedia = 'social_media';

    public function getLabel(): string
    {
        return match ($this) {
            self::Dev => 'Developer',
            self::Editorial => 'Editorial',
            self::SocialMedia => 'Social Media',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Dev => 'danger',
            self::Editorial => 'info',
            self::SocialMedia => 'warning',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Shield permission names granted to this role (format: "Action:Subject").
     *
     * @return list<string>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Dev => self::allPermissions(),
            self::Editorial => [
                ...self::resource('Program'),
                ...self::resource('ProgramSchedule'),
                ...self::resource('Announcer'),
                ...self::resource('Post'),
                ...self::studio(),
            ],
            self::SocialMedia => [
                ...self::resource('SocialHighlight'),
                ...self::resource('Event'),
                'ViewAny:Station',
                'View:Station',
                'ToggleVideoLive:Station',
                ...self::studio(),
            ],
        };
    }

    /**
     * Every permission the admin panel knows about.
     *
     * @return list<string>
     */
    public static function allPermissions(): array
    {
        $models = ['Station', 'Program', 'ProgramSchedule', 'Announcer', 'Post', 'Event', 'SocialHighlight', 'User'];

        return [
            ...array_merge(...array_map(self::resource(...), $models)),
            'ToggleVideoLive:Station',
            'View:StationSettings',
            ...self::studio(),
        ];
    }

    /**
     * Studio Mode: the live monitor page and chat moderation. Every admin role gets it.
     *
     * @return list<string>
     */
    private static function studio(): array
    {
        return ['View:StudioMode', 'Moderate:ChatMessage'];
    }

    /**
     * @return list<string>
     */
    private static function resource(string $subject): array
    {
        $methods = config('filament-shield.policies.methods', ['viewAny', 'view', 'create', 'update', 'delete', 'deleteAny']);

        return array_values(array_map(fn (string $method): string => Str::studly($method).":{$subject}", $methods));
    }
}
