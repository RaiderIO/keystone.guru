<?php

namespace App\Http\Middleware;

use App\Service\BannedIpAddress\BannedIpAddressServiceInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Teapot\StatusCode\RFC\RFC7231;

class BlockBannedIpAddresses
{
    public function __construct(private readonly BannedIpAddressServiceInterface $bannedIpAddressService)
    {
    }

    /**
     * Handle an incoming request.
     *
     * Registered after TrustProxies in bootstrap/app.php so $request->ip() already reflects the
     * real visitor IP resolved from CF-Connecting-IP, not the CloudFlare edge IP.
     */
    public function handle(Request $request, Closure $next): Response
    {
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
