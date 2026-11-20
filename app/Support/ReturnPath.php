<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Sends listeners back to the page they came from after signing in
 * (e.g. /login?return=/oz-bandung). Only same-site paths are accepted.
 */
class ReturnPath
{
    public static function remember(Request $request, string $key = 'return'): void
    {
        $path = $request->query($key);

        if (is_string($path) && self::isSafe($path)) {
            $request->session()->put('url.intended', url($path));
        }
    }

    public static function isSafe(string $path): bool
    {
        // A single leading slash, no scheme-relative "//host" or backslash tricks.
        return str_starts_with($path, '/')
            && ! str_starts_with($path, '//')
            && ! str_contains($path, '\\')
            && ! preg_match('/[\x00-\x1F]/', $path);
    }
}
