<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Works out how the public player should render a station's video stream URL.
 *
 * - YouTube links (watch, live, embed, youtu.be, live_stream?channel=) become an embed URL.
 * - .m3u8 links are HLS and play in a <video> element.
 * - Anything else is treated as an embeddable page and loaded in an iframe.
 */
class VideoStream
{
    public const TYPE_YOUTUBE = 'youtube';

    public const TYPE_HLS = 'hls';

    public const TYPE_EMBED = 'embed';

    /**
     * @return array{type: string, src: string}|null
     */
    public static function describe(?string $url): ?array
    {
        if (blank($url)) {
            return null;
        }

        if (Str::contains(Str::lower((string) parse_url($url, PHP_URL_PATH)), '.m3u8')) {
            return ['type' => self::TYPE_HLS, 'src' => $url];
        }

        if ($embed = self::youTubeEmbedUrl($url)) {
            return ['type' => self::TYPE_YOUTUBE, 'src' => $embed];
        }

        return ['type' => self::TYPE_EMBED, 'src' => $url];
    }

    public static function youTubeVideoId(?string $url): ?string
    {
        if (blank($url) || ! self::isYouTube($url)) {
            return null;
        }

        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $id = match (true) {
            str_contains((string) parse_url($url, PHP_URL_HOST), 'youtu.be') => Str::before($path, '/'),
            isset($query['v']) && is_string($query['v']) => $query['v'],
            (bool) preg_match('#^(?:embed|live|shorts|v)/([^/?]+)#', $path, $m) => $m[1],
            default => null,
        };

        return $id !== null && preg_match('/^[A-Za-z0-9_-]{6,20}$/', $id) && $id !== 'live_stream' ? $id : null;
    }

    private static function youTubeEmbedUrl(string $url): ?string
    {
        if (! self::isYouTube($url)) {
            return null;
        }

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        // A channel's current livestream: /embed/live_stream?channel=UC...
        if (isset($query['channel']) && is_string($query['channel'])) {
            return 'https://www.youtube.com/embed/live_stream?channel='.rawurlencode($query['channel']).'&autoplay=1';
        }

        $id = self::youTubeVideoId($url);

        return $id ? "https://www.youtube.com/embed/{$id}?autoplay=1" : null;
    }

    private static function isYouTube(string $url): bool
    {
        $host = Str::lower((string) parse_url($url, PHP_URL_HOST));

        return in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be', 'www.youtube-nocookie.com'], true);
    }
}
