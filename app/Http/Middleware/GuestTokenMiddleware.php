<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GuestTokenMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Set a persistent guest_token cookie for non-authenticated users
        if (!$request->user() && !$request->cookie('guest_token')) {
            $token = bin2hex(random_bytes(32));
            $response->cookie(
                'guest_token',
                $token,
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
