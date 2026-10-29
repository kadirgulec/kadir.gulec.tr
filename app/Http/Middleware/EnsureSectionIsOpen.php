<?php

namespace App\Http\Middleware;

use App\Enums\Section;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A closed section's pages answer 404, as if they never existed; whoever
 * manages the section still reads them, with a "kapalı" band (layouts/site).
 * Used as `section:goals`.
 */
class EnsureSectionIsOpen
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $section): Response
    {
        abort_unless(Section::from($section)->isReadableBy($request->user()), 404);

        return $next($request);
    }
}
