<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpFoundation\Response;

/**
 * Disables Laravel's `{ "data": … }` envelope for v2 responses so both single
 * resources AND resource *collections* serialize raw (API_REFERENCE.md §5).
 *
 * Per-class `$wrap = null` only unwraps single resources; AnonymousResourceCollection
 * resolves wrapping from the base JsonResource, so a route-scoped
 * `JsonResource::withoutWrapping()` is required for collections. Applied only to
 * the `api/v2` group — v1 and the web app use no API Resources, so this is inert
 * to them.
 */
class ForceRawJsonResources
{
    public function handle(Request $request, Closure $next): Response
    {
        JsonResource::withoutWrapping();

        return $next($request);
    }
}
