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
    private function makeMiddleware(): TrustProxies
    {
        $cloudflareService = $this->createMockPublic(CloudflareServiceInterface::class);
        $cloudflareService->method('getIpRanges')
            ->willReturn(self::CLOUDFLARE_RANGES);

        return new TrustProxies($cloudflareService);
    }
}
