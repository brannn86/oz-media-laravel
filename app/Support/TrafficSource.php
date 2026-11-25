<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Where a visit came from, in words people use: a UTM source when the link
 * had one, else the referring site, else "Direct".
 */
final class TrafficSource
{
    public const DIRECT = 'Direct';

    /** Referrer host endings (and utm_source values) => source name. */
    private const KNOWN = [
        'instagram.com' => 'Instagram',
        'instagram' => 'Instagram',
        'ig' => 'Instagram',
        'facebook.com' => 'Facebook',
        'fb.me' => 'Facebook',
        'facebook' => 'Facebook',
        'fb' => 'Facebook',
        'tiktok.com' => 'TikTok',
        'tiktok' => 'TikTok',
        'youtube.com' => 'YouTube',
        'youtu.be' => 'YouTube',
        'youtube' => 'YouTube',
        'threads.net' => 'Threads',
        'threads.com' => 'Threads',
        'threads' => 'Threads',
        'x.com' => 'X',
        't.co' => 'X',
        'twitter.com' => 'X',
        'twitter' => 'X',
        'whatsapp.com' => 'WhatsApp',
        'wa.me' => 'WhatsApp',
        'whatsapp' => 'WhatsApp',
        'google' => 'Google',
        'bing.com' => 'Bing',
        'bing' => 'Bing',
        'duckduckgo.com' => 'DuckDuckGo',
        'ozradio.id' => 'ozradio.id',
    ];

    public static function classify(?string $referrerHost, ?string $utmSource): string
    {
        $utm = Str::lower(trim((string) $utmSource));

        if ($utm !== '') {
            return self::KNOWN[$utm] ?? Str::limit($utm, 60, '');
        }

        $host = Str::lower(trim((string) $referrerHost));

        if ($host === '') {
            return self::DIRECT;
        }

        // google.com, google.co.id, news.google.com, ...
        if (preg_match('/(^|\.)google\.[a-z.]+$/', $host)) {
            return 'Google';
        }

        foreach (self::KNOWN as $domain => $name) {
            if (str_contains($domain, '.') && ($host === $domain || str_ends_with($host, ".{$domain}"))) {
                return $name;
            }
        }

        return Str::limit(Str::after($host, 'www.'), 60, '');
    }
}
