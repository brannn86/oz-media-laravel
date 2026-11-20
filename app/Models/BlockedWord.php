<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * A word the live chat masks (see App\Support\ProfanityFilter).
 *
 * @property int $id
 * @property string $word Always stored lower-case.
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Table('chat_blocked_words')]
#[Fillable(['word'])]
class BlockedWord extends Model
{
    private const CACHE_KEY = 'chat:blocked-words';

    protected static function booted(): void
    {
        static::saving(function (BlockedWord $word): void {
            $word->word = mb_strtolower(trim($word->word));
        });

        // Every chat message reads the list, so it is cached until it changes.
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /**
     * @return list<string>
     */
    public static function cachedList(): array
    {
        $words = Cache::rememberForever(
            self::CACHE_KEY,
            fn (): array => static::query()->orderBy('word')->pluck('word')->all(),
        );

        return array_values(array_filter($words, is_string(...)));
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
