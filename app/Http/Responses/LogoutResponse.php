<?php

namespace App\Http\Responses;

use App\Support\ReturnPath;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

/**
 * Logging out from a station page keeps the listener on that station
 * (the session, and with it url.intended, is gone by now).
 */
class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request): Response
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 204);
        }

        $path = $request->input('return');

        return redirect(is_string($path) && ReturnPath::isSafe($path) ? $path : Fortify::redirects('logout', '/'));
    }
}
