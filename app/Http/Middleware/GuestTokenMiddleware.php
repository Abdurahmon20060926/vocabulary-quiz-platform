<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GuestTokenMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $guestToken = $request->cookie('guest_token');

        if (!$request->user() && !$guestToken) {
            $guestToken = bin2hex(random_bytes(32));
            $request->attributes->set('guest_token', $guestToken);
        }

        $response = $next($request);

        // Set a persistent guest_token cookie for non-authenticated users
        if (!$request->user() && $guestToken && !$request->cookie('guest_token')) {
            $response->cookie(
                'guest_token',
                $guestToken,
                60 * 24 * 365, // 1 year
                '/',
                null,
                false,
                true // httpOnly
            );
        }

        return $response;
    }
}
