<?php

namespace App\Http\Middleware;

use App\Service\Cloudflare\CloudflareServiceInterface;
use Closure;
use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrustProxies extends Middleware
{
    /** @var array<int, string>|string|null */
    protected $proxies;

    /** @var int */
    protected $headers = Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;

    public function __construct(private readonly CloudflareServiceInterface $cloudflareService)
    {
    }

    #[\Override]
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->isProduction()) {
            // Prefer caching inside the service so this isn’t fetched every request.
            // https://khalilst.medium.com/get-real-client-ip-behind-cloudflare-in-laravel-189cb89059ff
            $this->proxies = $this->cloudflareService->getIpRanges();
        } else {
            $this->proxies = null; // no trusted proxies locally
        }

        return parent::handle($request, $next);
    }
}
