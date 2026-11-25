<?php

namespace App\Support;

/**
 * Plain-words reading of a user agent string, for analytics only: device
 * kind, operating system, browser (in-app browsers of social apps named as
 * such), and for Icecast clients, what kind of player it is.
 *
 * Deliberately small: a few well-known patterns, no device database.
 */
final class UserAgent
{
    public const PHONE = 'Phone';

    public const TABLET = 'Tablet';

    public const DESKTOP = 'Desktop';

    public const OTHER = 'Other';

    public static function device(?string $ua): string
    {
        $ua = (string) $ua;

        return match (true) {
            $ua === '' => self::OTHER,
            (bool) preg_match('/iPad|Tablet|PlayBook|Silk|Kindle|SM-T\d|Android(?!.*Mobile)/i', $ua) => self::TABLET,
            (bool) preg_match('/Mobi|iPhone|iPod|Android|Windows Phone/i', $ua) => self::PHONE,
            (bool) preg_match('/Windows NT|Macintosh|X11|Linux|CrOS/i', $ua) => self::DESKTOP,
            default => self::OTHER,
        };
    }

    public static function os(?string $ua): ?string
    {
        $ua = (string) $ua;

        return match (true) {
            $ua === '' => null,
            (bool) preg_match('/iPhone|iPad|iPod/i', $ua) => 'iOS',
            (bool) preg_match('/Android/i', $ua) => 'Android',
            (bool) preg_match('/CrOS/i', $ua) => 'ChromeOS',
            (bool) preg_match('/Windows/i', $ua) => 'Windows',
            (bool) preg_match('/Macintosh|Mac OS X/i', $ua) => 'macOS',
            (bool) preg_match('/Linux/i', $ua) => 'Linux',
            default => 'Other',
        };
    }

    public static function browser(?string $ua): ?string
    {
        $ua = (string) $ua;

        return match (true) {
            $ua === '' => null,
            // In-app browsers first: they also say "Safari" / "Chrome".
            (bool) preg_match('/Instagram/i', $ua) => 'Instagram (in-app)',
            (bool) preg_match('/FBAN|FBAV|FB_IAB/i', $ua) => 'Facebook (in-app)',
            (bool) preg_match('/TikTok|musical_ly|BytedanceWebview|ByteLocale/i', $ua) => 'TikTok (in-app)',
            (bool) preg_match('/\bLine\//i', $ua) => 'LINE (in-app)',
            (bool) preg_match('/Edg(A|iOS)?\//i', $ua) => 'Edge',
            (bool) preg_match('/SamsungBrowser/i', $ua) => 'Samsung Internet',
            (bool) preg_match('/OPR\/|Opera/i', $ua) => 'Opera',
            (bool) preg_match('/Firefox|FxiOS/i', $ua) => 'Firefox',
            (bool) preg_match('/Chrome|CriOS|Chromium/i', $ua) => 'Chrome',
            (bool) preg_match('/Safari/i', $ua) => 'Safari',
            default => 'Other',
        };
    }

    /**
     * What kind of player an Icecast listener is, from its user agent.
     */
    public static function icecastClient(?string $ua): string
    {
        $ua = (string) $ua;

        return match (true) {
            $ua === '' => 'Unknown',
            (bool) preg_match('/OZ ?(Radio|Media)|ozmedia/i', $ua) => 'OZ app',
            (bool) preg_match('/Alexa|AmazonEcho|Echo|Sonos|Bose|CrKey|Google ?Home|Nest|HomePod|Roku|Fire ?TV/i', $ua) => 'Smart speakers & TVs',
            (bool) preg_match('/CarPlay|Android ?Auto|AAOS|Tesla|QtCarBrowser/i', $ua) => 'Car',
            (bool) preg_match('/TuneIn|radio\.net|radio\.de|myTuner|Radio ?Garden|radiogarden|Streema|Simple ?Radio|Audials|Replaio|RadioDroid|Radioplayer/i', $ua) => 'Radio apps',
            // iPhone/iPad Safari plays audio through Apple's media stack, as do iOS apps.
            (bool) preg_match('/AppleCoreMedia|CFNetwork/i', $ua) => 'Apple devices (Safari & apps)',
            (bool) preg_match('/stagefright|ExoPlayer|Dalvik/i', $ua) => 'Android apps',
            (bool) preg_match('/VLC|Winamp|foobar|iTunes|Lavf|ffmpeg|mpv|MPlayer|Windows-Media-Player|NSPlayer|Kodi/i', $ua) => 'Media players',
            (bool) preg_match('/Mozilla|Chrome|Safari|Firefox|Edg/i', $ua) => 'Web browsers',
            default => 'Other',
        };
    }
}
