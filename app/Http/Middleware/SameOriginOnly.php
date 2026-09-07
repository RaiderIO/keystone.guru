<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rejects requests that were not issued by one of our own pages.
 *
 * Sec-Fetch-Site is a forbidden header name, so page JavaScript cannot forge it, and it stays
 * 'same-origin' for the XHRs of an embed page rendered inside a third-party iframe - the document
 * itself is ours. A non-browser client can still set it (and Referer) freely, so this filters naive
 * callers only; it is not an access control.
 */
class SameOriginOnly
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->isSameOrigin($request) && config('app.env') !== 'local') {
            return response('Forbidden', 403);
        }

        return $next($request);
    }

    private function isSameOrigin(Request $request): bool
    {
        $secFetchSite = $request->headers->get('Sec-Fetch-Site');

        // Browsers only attach fetch metadata when the target is a trustworthy origin, so the
        // header is absent over plain HTTP (local/dev) - but when it is there it is authoritative,
        // and falling through to the Referer would only weaken the check.
        if ($secFetchSite !== null) {
            return $secFetchSite === 'same-origin';
        }

        $referer = $request->headers->get('Referer');
        if ($referer === null) {
            return false;
        }

        // Host only: the request's own scheme is not trustworthy on every environment (TrustProxies
        // only honours CloudFlare's forwarded headers in production), so comparing it would reject
        // legitimate requests everywhere else.
        $refererHost = parse_url($referer, PHP_URL_HOST);

        return is_string($refererHost) && strcasecmp($refererHost, $request->getHost()) === 0;
    }
}
