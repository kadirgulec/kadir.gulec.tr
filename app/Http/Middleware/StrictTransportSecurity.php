<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * HSTS: tells the browser to reach this site only over HTTPS for the next
 * year, so after the first visit it never even tries http:// (no redirect
 * hop, no chance to be downgraded on the way). Only in production and only
 * on HTTPS responses; local http development stays untouched.
 */
class StrictTransportSecurity
{
    /** One year, in seconds. */
    public const MAX_AGE = 31536000;

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (app()->isProduction() && $request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age='.self::MAX_AGE);
        }

        return $response;
    }
}
