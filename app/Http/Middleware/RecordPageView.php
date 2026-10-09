<?php

namespace App\Http\Middleware;

use App\Enums\Permission;
use App\Models\PageView;
use App\Support\UserAgentInfo;
use App\Support\VisitorHash;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

use function Illuminate\Support\defer;

/**
 * Counts a view of a public page for the visit statistics (/admin/ziyaretciler).
 * Nothing is written to the browser: no cookie, no script. Only the path, the
 * referring site, a coarse browser/system/device and a daily visitor hash
 * (see VisitorHash) are stored, after the response has been sent.
 *
 * Skipped: anything but a successful HTML page, bots, prefetches, visitors who
 * ask not to be tracked (Do Not Track / Global Privacy Control) and the admins
 * themselves.
 */
class RecordPageView
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldRecord($request, $response)) {
            $userAgent = new UserAgentInfo((string) $request->userAgent());

            $attributes = [
                'path' => Str::limit('/'.ltrim($request->path(), '/'), 255, ''),
                'referrer_host' => $this->referrerHost($request),
                'utm_source' => $this->campaignSource($request),
                'visitor_hash' => VisitorHash::for($request),
                'browser' => $userAgent->browser(),
                'os' => $userAgent->os(),
                'device' => $userAgent->device(),
            ];

            defer(fn () => PageView::query()->create($attributes));
        }

        return $response;
    }

    private function shouldRecord(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET')
            || $response->getStatusCode() !== 200
            || ! str_starts_with((string) $response->headers->get('Content-Type'), 'text/html')) {
            return false;
        }

        if ($request->header('DNT') === '1'
            || $request->header('Sec-GPC') === '1'
            || in_array('prefetch', [$request->header('Purpose'), $request->header('Sec-Purpose')], true)) {
            return false;
        }

        if (blank($request->header('Accept-Language')) || (new UserAgentInfo((string) $request->userAgent()))->isBot()) {
            return false;
        }

        return ! $request->user()?->can(Permission::AccessAdmin->value);
    }

    /**
     * The referring site without "www.", or null for direct visits and links
     * inside this site. The rest of the address is dropped: it can carry
     * search terms or other personal details.
     */
    private function referrerHost(Request $request): ?string
    {
        $host = parse_url((string) $request->headers->get('referer'), PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return null;
        }

        $host = Str::lower(Str::chopStart($host, 'www.'));

        return $host === Str::chopStart($request->getHost(), 'www.') ? null : Str::limit($host, 255, '');
    }

    private function campaignSource(Request $request): ?string
    {
        $source = $request->query('utm_source') ?? $request->query('ref');

        return is_string($source) && trim($source) !== '' ? Str::limit(Str::lower(trim($source)), 100, '') : null;
    }
}
