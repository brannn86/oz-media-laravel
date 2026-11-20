<?php

namespace App\Support;

use App\Models\BlockedWord;

/**
 * Masks blocked words in chat messages: "anjing" -> "a*****".
 *
 * Whole-word, case-insensitive, and tolerant of stretched letters
 * ("anjiiing"). Deliberately basic; live moderation in Studio Mode
 * covers what a word list cannot.
 */
class ProfanityFilter
{
    /**
     * @param  list<string>  $words
     */
    public function __construct(private readonly array $words) {}

    /**
     * The list moderators edit in the admin (Live Chat > Blocked Words).
     */
    public static function fromDatabase(): self
    {
        return new self(BlockedWord::cachedList());
    }

    public function clean(string $text): string
    {
        if ($this->words === []) {
            return $text;
        }

        return (string) preg_replace_callback(
            $this->pattern(),
            fn (array $match): string => mb_substr($match[0], 0, 1).str_repeat('*', max(mb_strlen($match[0]) - 1, 1)),
            $text,
        );
    }

    public function containsProfanity(string $text): bool
    {
        return $this->words !== [] && preg_match($this->pattern(), $text) === 1;
    }

    private function pattern(): string
    {
        $alternatives = array_map(
            // Each letter may repeat: "anjing" also matches "anjiiing".
            fn (string $word): string => implode('', array_map(
                fn (string $letter): string => preg_quote($letter, '/').'+',
                mb_str_split(mb_strtolower($word)),
            )),
            $this->words,
        );

        return '/(?<![\p{L}\p{N}])(?:'.implode('|', $alternatives).')(?![\p{L}\p{N}])/iu';
    }
}
