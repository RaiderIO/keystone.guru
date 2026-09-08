<?php

namespace Tests\Unit\App\Http\Middleware;

use App\Http\Middleware\TrustProxies;
use App\Service\Cloudflare\CloudflareServiceInterface;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCases\PublicTestCase;

#[Group('Middleware')]
#[Group('TrustProxies')]
class TrustProxiesTest extends PublicTestCase
{
    /** @var array<int, string> A CloudFlare range (172.64.0.0/13) plus an arbitrary one for coverage. */
    private const array CLOUDFLARE_RANGES = ['172.64.0.0/13', '173.245.48.0/20'];

    /** @var array<int, string> The VPC the ALB's ENIs live in - the hop directly in front of the app. */
    private const array VPC_CIDRS = ['172.41.0.0/16'];

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        // The middleware only trusts CloudFlare and honours CF-Connecting-IP in production.
        $this->app->detectEnvironment(static fn() => 'production');
    }

    #[\Override]
    protected function tearDown(): void
    {
        // setTrustedProxies mutates static state on the Symfony request, so reset it between tests.
        Request::setTrustedProxies([], Request::HEADER_X_FORWARDED_FOR);

        parent::tearDown();
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_GivenTrustedCloudflarePeerWithConnectingIp_ReturnsConnectingIp(): void
    {
        // Arrange - a genuine CloudFlare peer, with both an X-Forwarded-For entry and CF-Connecting-IP that disagree.
        $middleware = $this->makeMiddleware();
        $request    = Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => '172.68.0.1']);
        $request->headers->set('X-Forwarded-For', '203.0.113.7');
        $request->headers->set('CF-Connecting-IP', '198.51.100.42');

        // Act
        $middleware->handle($request, static fn() => new Response());

        // Assert - CF-Connecting-IP is authoritative over whatever sits in X-Forwarded-For.
        self::assertSame('198.51.100.42', $request->ip());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_GivenTrustedCloudflarePeerWithoutForwardedFor_ReturnsConnectingIpInsteadOfEdgeIp(): void
    {
        // Arrange - the bug case: no usable X-Forwarded-For, so Symfony would otherwise fall back to the edge IP.
        $middleware = $this->makeMiddleware();
        $request    = Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => '172.68.0.1']);
        $request->headers->set('CF-Connecting-IP', '198.51.100.42');

        // Act
        $middleware->handle($request, static fn() => new Response());

        // Assert - the real visitor IP, not the CloudFlare edge IP (172.68.0.1).
        self::assertSame('198.51.100.42', $request->ip());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_GivenUntrustedPeerSpoofingConnectingIp_IgnoresConnectingIp(): void
    {
        // Arrange - a client reaching the origin outside of CloudFlare, spoofing CF-Connecting-IP.
        $middleware = $this->makeMiddleware();
        $request    = Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => '203.0.113.99']);
        $request->headers->set('CF-Connecting-IP', '10.0.0.1');

        // Act
        $middleware->handle($request, static fn() => new Response());

        // Assert - the spoofed header is ignored; the real connecting peer is used.
        self::assertSame('203.0.113.99', $request->ip());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_GivenLoadBalancerPeerForwardingCloudflareChain_ReturnsVisitorIp(): void
    {
        // Arrange - the real production shape: the ALB is the peer and appended the CloudFlare edge
        // to the X-Forwarded-For CloudFlare had already set to the visitor.
        config()->set('keystoneguru.trusted_proxies.vpc_cidrs', self::VPC_CIDRS);
        $middleware = $this->makeMiddleware();
        $request    = Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => '172.41.23.123']);
        $request->headers->set('X-Forwarded-For', '203.0.113.7, 172.68.0.1');

        // Act
        $middleware->handle($request, static fn() => new Response());

        // Assert - the visitor, not the load balancer ENI and not the CloudFlare edge.
        self::assertSame('203.0.113.7', $request->ip());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_GivenLoadBalancerPeerWithForgedForwardedForPrefix_ReturnsRealClientIp(): void
    {
        // Arrange - a client reaching the internet-facing ALB directly, sending its own
        // X-Forwarded-For. The ALB appends the real connecting peer to whatever it was given.
        config()->set('keystoneguru.trusted_proxies.vpc_cidrs', self::VPC_CIDRS);
        $middleware = $this->makeMiddleware();
        $request    = Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => '172.41.23.123']);
        $request->headers->set('X-Forwarded-For', '10.0.0.1, 203.0.113.99');

        // Act
        $middleware->handle($request, static fn() => new Response());

        // Assert - the forged entry to the left is ignored; the appended real client wins.
        self::assertSame('203.0.113.99', $request->ip());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_GivenLoadBalancerPeerSpoofingConnectingIp_IgnoresConnectingIp(): void
    {
        // Arrange - CF-Connecting-IP is only honoured when the peer is CloudFlare itself. The load
        // balancer is trusted for the chain walk but is reachable without CloudFlare, so a header
        // forged on that hop must not be believed.
        config()->set('keystoneguru.trusted_proxies.vpc_cidrs', self::VPC_CIDRS);
        $middleware = $this->makeMiddleware();
        $request    = Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => '172.41.23.123']);
        $request->headers->set('X-Forwarded-For', '203.0.113.99');
        $request->headers->set('CF-Connecting-IP', '10.0.0.1');

        // Act
        $middleware->handle($request, static fn() => new Response());

        // Assert - the spoofed header is ignored in favour of the forwarded chain.
        self::assertSame('203.0.113.99', $request->ip());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_GivenLoadBalancerPeerForgingForwardedHost_KeepsTheRealHost(): void
    {
        // Arrange - a request reaching the internet-facing ALB directly, carrying the legitimate
        // Host alongside an attacker-controlled X-Forwarded-Host. Neither CloudFlare nor the ALB
        // sends an authoritative forwarded host, so this header is whatever the client typed.
        config()->set('keystoneguru.trusted_proxies.vpc_cidrs', self::VPC_CIDRS);
        $middleware = $this->makeMiddleware();
        $request    = Request::create('/', 'GET', [], [], [], [
            'REMOTE_ADDR' => '172.41.23.123',
            'HTTP_HOST'   => 'keystone.guru',
        ]);
        $request->headers->set('X-Forwarded-For', '203.0.113.7, 172.68.0.1');
        $request->headers->set('X-Forwarded-Host', 'evil.example.com');

        // Act
        $middleware->handle($request, static fn() => new Response());

        // Assert - the forged host is ignored, so generated absolute URLs stay on our domain.
        self::assertSame('keystone.guru', $request->getHost());
        self::assertSame('203.0.113.7', $request->ip());
    }

    /**
     * @throws Exception
     */
    private function makeMiddleware(): TrustProxies
    {
        $cloudflareService = $this->createMockPublic(CloudflareServiceInterface::class);
        $cloudflareService->method('getIpRanges')
            ->willReturn(self::CLOUDFLARE_RANGES);

        return new TrustProxies($cloudflareService);
    }
}
