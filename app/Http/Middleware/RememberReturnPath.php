<?php

namespace App\Http\Middleware;

use App\Support\ReturnPath;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Remembers a same-site `return` path (query string or form field) on the
 * Fortify routes, so logging in or signing up from the station popup lands
 * back on the page the listener was on, including after a 2FA challenge.
 */
class RememberReturnPath
{
    public function handle(Request $request, Closure $next): Response
    {
        ReturnPath::remember($request);

        return $next($request);
    }
}
