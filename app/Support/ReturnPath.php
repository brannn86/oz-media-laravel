<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Sends listeners back to the page they came from after signing in
 * (e.g. /login?return=/oz-bandung, or a `return` form field on POST /login).
 * Only same-site paths are accepted.
 */
class ReturnPath
{
    public static function remember(Request $request, string $key = 'return'): void
    {
        $path = self::from($request, $key);

        if ($path !== null) {
            $request->session()->put('url.intended', url($path));
        }
    }

    /**
     * The request's `return` path, or null when it is missing or unsafe.
     */
    public static function from(Request $request, string $key = 'return'): ?string
    {
        $path = $request->input($key);

        return is_string($path) && self::isSafe($path) ? $path : null;
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
