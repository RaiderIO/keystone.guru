<?php

namespace App\Http\Middleware;

use App\Service\Cloudflare\CloudflareServiceInterface;
use Closure;
use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;
use Override;
use Symfony\Component\HttpFoundation\IpUtils;
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

    #[Override]
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->isProduction()) {
            // Prefer caching inside the service so this isn’t fetched every request.
            // https://khalilst.medium.com/get-real-client-ip-behind-cloudflare-in-laravel-189cb89059ff
            $this->proxies = $this->cloudflareService->getIpRanges();

            $this->useCloudflareConnectingIp($request);
        } else {
            $this->proxies = null; // no trusted proxies locally
        }

        return parent::handle($request, $next);
    }

    /**
     * CloudFlare sends the real visitor IP in the CF-Connecting-IP header on every proxied request. It is a single
     * value, so it sidesteps the X-Forwarded-For chain walk which - when it fails to yield a non-trusted client entry -
     * falls back to returning the CloudFlare edge IP itself, bucketing swaths of unrelated visitors under one IP for
     * rate limiting. Rewriting X-Forwarded-For to this single value lets the parent middleware resolve
     * $request->ip() to the real visitor.
     *
     * This is only honoured when the connecting peer is itself a trusted CloudFlare proxy - the same trust boundary the
     * parent middleware enforces - otherwise a client reaching the origin outside of CloudFlare could spoof the header
     * and evade rate limiting entirely.
     */
    private function useCloudflareConnectingIp(Request $request): void
    {
        if (!$request->headers->has('CF-Connecting-IP')) {
            return;
        }

        if (!IpUtils::checkIp((string)$request->server->get('REMOTE_ADDR', ''), $this->proxies ?? [])) {
            return;
        }

        $request->headers->set('X-Forwarded-For', (string)$request->headers->get('CF-Connecting-IP'));
    }
}
