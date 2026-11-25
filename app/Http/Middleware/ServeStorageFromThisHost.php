<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Builds public-disk URLs on the host the admin is open on.
 *
 * Filament's file upload fetches each saved file to show it. From
 * admin.ozmedianet.com (or localhost when APP_URL says 127.0.0.1) a URL on
 * APP_URL's host is cross-origin, the browser blocks it, and the upload
 * stays on "Waiting for size" with no way to remove it. Every host serves
 * the same public/ folder, so the file is there on this host too.
 */
class ServeStorageFromThisHost
{
    public function handle(Request $request, Closure $next): Response
    {
        config(['filesystems.disks.public.url' => $request->getSchemeAndHttpHost().'/storage']);
        Storage::forgetDisk('public');

        return $next($request);
    }
}
