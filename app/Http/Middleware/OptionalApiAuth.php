<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Optional Passport authentication for v2 "public-capable" endpoints (A6):
 * system status / update / onboarding can be shown pre-login, but the client
 * still sends a bearer when it has one.
 *
 * If a valid `api` (Passport) token is present, the resolved user is bound to the
 * request; otherwise the request proceeds unauthenticated. It never returns 401.
 * New middleware (registered under a new alias) — does not touch the web stack
 * (GR4).
 */
class OptionalApiAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->bearerToken()) {
            $user = auth('api')->user();

            if ($user) {
                auth()->setUser($user);
            }
        }

        return $next($request);
    }
}
