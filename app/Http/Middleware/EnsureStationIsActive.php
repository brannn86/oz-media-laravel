<?php

namespace App\Http\Middleware;

use App\Models\Station;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 404s any route whose bound {station} is inactive (API and web alike).
 */
class EnsureStationIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $station = $request->route('station');

        abort_unless($station instanceof Station && $station->is_active, 404);

        return $next($request);
    }
}
