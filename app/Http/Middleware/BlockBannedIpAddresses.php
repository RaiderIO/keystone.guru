<?php

namespace App\Http\Middleware;

use App\Service\BannedIpAddress\BannedIpAddressServiceInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Teapot\StatusCode\RFC\RFC7231;

class BlockBannedIpAddresses
{
    /** The load balancer's health probe must answer even when the ban list's cache or database is down. */
    private const array EXEMPT_PATHS = ['health/app'];

    public function __construct(private readonly BannedIpAddressServiceInterface $bannedIpAddressService)
    {
    }

    /**
     * Handle an incoming request.
     *
     * Registered after TrustProxies in bootstrap/app.php so $request->ip() already reflects the
     * real visitor IP resolved from the forwarded chain, not the load balancer or CloudFlare edge.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is(...self::EXEMPT_PATHS)) {
            return $next($request);
        }

        if ($this->bannedIpAddressService->isBanned((string)$request->ip())) {
            if ($request->ajax() || $request->isJson()) {
                return response(json_encode([
                    'message' => 'Forbidden',
                ]), RFC7231::FORBIDDEN);
            }

            return response('Forbidden', RFC7231::FORBIDDEN);
        }

        return $next($request);
    }
}
