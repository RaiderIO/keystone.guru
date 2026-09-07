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

    /**
     * X-Forwarded-Host is deliberately absent. Neither CloudFlare nor the ALB sends an
     * authoritative forwarded host - Symfony's own HEADER_X_FORWARDED_AWS_ELB preset omits it for
     * that reason - so the header arrives exactly as the client sent it. Trusting it would let a
     * request that reaches the internet-facing ALB directly set getHost() to any domain, poisoning
     * every generated absolute URL (password reset links, the guest login redirect) with no host
     * allowlist to catch it.
     *
     * @var int
     */
    protected $headers = Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;

    public function __construct(private readonly CloudflareServiceInterface $cloudflareService)
    {
    }

    /**
     * Every hop in front of the application has to be trusted before Symfony will walk the
     * X-Forwarded-For chain back to the visitor. Requests arrive as
     * visitor -> CloudFlare -> ALB -> here, and the ALB runs with
     * routing.http.xff_header_processing.mode=append, so what lands is
     * `X-Forwarded-For: <visitor>, <cloudflare edge>` with the ALB as the connecting peer.
     *
     * Trusting only CloudFlare's ranges left that peer untrusted, which stops the chain being
     * walked at all, and $request->ip() then returned the load balancer's own ENI for every
     * request - collapsing every anonymous visitor into one bucket for rate limiting and IP
     * bans (#4536).
     */
    #[Override]
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->isProduction()) {
            // Prefer caching inside the service so this isn’t fetched every request.
            // https://khalilst.medium.com/get-real-client-ip-behind-cloudflare-in-laravel-189cb89059ff
            $cloudflareRanges = $this->cloudflareService->getIpRanges();

            /** @var array<int, string> $loadBalancerCidrs */
            $loadBalancerCidrs = config('keystoneguru.trusted_proxies.load_balancer_cidrs');

            $this->proxies = array_merge($cloudflareRanges, $loadBalancerCidrs);

            $this->useCloudflareConnectingIp($request, $cloudflareRanges);
        } else {
            $this->proxies = null; // no trusted proxies locally
        }

        return parent::handle($request, $next);
    }

    /**
     * CloudFlare sends the real visitor IP in the CF-Connecting-IP header on every proxied request.
     * It is a single value, so it sidesteps the X-Forwarded-For chain walk which - when it fails to
     * yield a non-trusted client entry - falls back to returning the CloudFlare edge IP itself,
     * bucketing swaths of unrelated visitors under one IP for rate limiting. Rewriting
     * X-Forwarded-For to this single value lets the parent middleware resolve $request->ip() to the
     * real visitor.
     *
     * The peer is checked against CloudFlare's ranges specifically rather than against the full
     * trusted-proxy list. The load balancer is also a trusted proxy, but it is internet-facing and
     * reachable without going through CloudFlare at all, so honouring a client-settable header on
     * that hop would let anyone reaching the origin directly claim any IP they like. Behind the ALB
     * this check therefore never passes, and the X-Forwarded-For chain walk resolves the visitor
     * instead - which is spoof-resistant because the ALB appends the real connecting peer, leaving
     * any forged entries to the left of it.
     *
     * @param array<int, string> $cloudflareRanges
     */
    private function useCloudflareConnectingIp(Request $request, array $cloudflareRanges): void
    {
        if (!$request->headers->has('CF-Connecting-IP')) {
            return;
        }

        if (!IpUtils::checkIp((string)$request->server->get('REMOTE_ADDR', ''), $cloudflareRanges)) {
            return;
        }

        $request->headers->set('X-Forwarded-For', (string)$request->headers->get('CF-Connecting-IP'));
    }
}
