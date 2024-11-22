<?php

namespace App\Http\Middleware;

use App\Service\Cloudflare\CloudflareServiceInterface;
use Closure;
use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies;

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;

    public function __construct(
        private readonly CloudflareServiceInterface $cloudflareService
    ) {
    }

    public function handle(Request $request, Closure $next)
    {
        // https://developers.cloudflare.com/fundamentals/reference/http-request-headers/
        if (app()->isProduction()) {
            // Ensure that we know the original IP address that made the request
            // https://khalilst.medium.com/get-real-client-ip-behind-cloudflare-in-laravel-189cb89059ff
            Request::setTrustedProxies(
                $this->cloudflareService->getIpRanges(),
                $this->headers
            );
        }

        return parent::handle($request, $next);
    }
}
