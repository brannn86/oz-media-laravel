<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Turns a stored upload path (Filament FileUpload, public disk) into a public URL.
 */
class MediaUrl
{
    public static function for(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '//'])) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
