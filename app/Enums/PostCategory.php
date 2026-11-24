<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * The OZ News sections. The value is the URL slug (/news?category=arts-and-culture).
 */
enum PostCategory: string implements HasLabel
{
    case ArtsAndCulture = 'arts-and-culture';
    case Business = 'business';
    case Comedy = 'comedy';
    case Crime = 'crime';
    case Education = 'education';
    case Entertainment = 'entertainment';
    case Environment = 'environment';
    case Media = 'media';
    case Politics = 'politics';
    case Religion = 'religion';
    case Science = 'science';
    case Sports = 'sports';
    case Tech = 'tech';

    public function getLabel(): string
    {
        return match ($this) {
            self::ArtsAndCulture => 'Arts and Culture',
            self::Business => 'Business',
            self::Comedy => 'Comedy',
            self::Crime => 'Crime',
            self::Education => 'Education',
            self::Entertainment => 'Entertainment',
            self::Environment => 'Environment',
            self::Media => 'Media',
            self::Politics => 'Politics',
            self::Religion => 'Religion',
            self::Science => 'Science',
            self::Sports => 'Sports',
            self::Tech => 'Tech',
        };
    }

    /**
     * @return array{value: string, label: string}
     */
    public function toArray(): array
    {
        return ['value' => $this->value, 'label' => $this->getLabel()];
    }
}
