<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * Who is behind a request, for throttling and counting (not for auth):
 * the account when signed in, else the app's X-Listener-Id, else the web
 * session, falling back to the IP.
 */
final class ViewerKey
{
    public static function from(Request $request): string
    {
        $user = $request->user('sanctum');

        return match (true) {
            $user instanceof User => "user:{$user->id}",
            $request->hasHeader('X-Listener-Id') => 'device:'.$request->header('X-Listener-Id'),
            $request->hasSession() => 'session:'.$request->session()->getId(),
            default => 'ip:'.$request->ip(),
        };
    }
}
