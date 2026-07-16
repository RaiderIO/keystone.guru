<?php

namespace Tests\Unit\App\Http\Middleware;

use App\Http\Middleware\BlockBannedIpAddresses;
use App\Service\BannedIpAddress\BannedIpAddressServiceInterface;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCases\PublicTestCase;

#[Group('Middleware')]
#[Group('BlockBannedIpAddresses')]
class BlockBannedIpAddressesTest extends PublicTestCase
{
    /**
     * @throws Exception
     */
    #[Test]
    public function handle_GivenBannedIp_ReturnsForbiddenWithoutCallingNext(): void
    {
        // Arrange
        $bannedIpAddressService = $this->createMockPublic(BannedIpAddressServiceInterface::class);
        $bannedIpAddressService->expects($this->once())
            ->method('isBanned')
            ->with('203.0.113.1')
            ->willReturn(true);

        $middleware = new BlockBannedIpAddresses($bannedIpAddressService);
        $request    = Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => '203.0.113.1']);

        // Act
        $response = $middleware->handle($request, function () {
            self::fail('$next should not be called for a banned IP');
        });

        // Assert
        self::assertSame(403, $response->getStatusCode());
        self::assertSame('Forbidden', $response->getContent());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_GivenBannedIpAjaxRequest_ReturnsForbiddenJson(): void
    {
        // Arrange
        $bannedIpAddressService = $this->createMockPublic(BannedIpAddressServiceInterface::class);
        $bannedIpAddressService->method('isBanned')->willReturn(true);

        $middleware = new BlockBannedIpAddresses($bannedIpAddressService);
        $request    = Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => '203.0.113.1']);
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        // Act
        $response = $middleware->handle($request, function () {
            self::fail('$next should not be called for a banned IP');
        });

        // Assert
        self::assertSame(403, $response->getStatusCode());
        self::assertJson((string)$response->getContent());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_GivenNonBannedIp_CallsNext(): void
    {
        // Arrange
        $bannedIpAddressService = $this->createMockPublic(BannedIpAddressServiceInterface::class);
        $bannedIpAddressService->method('isBanned')->willReturn(false);

        $middleware = new BlockBannedIpAddresses($bannedIpAddressService);
        $request    = Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => '198.51.100.1']);

        $expectedResponse = new Response('OK');

        // Act
        $response = $middleware->handle($request, static fn() => $expectedResponse);

        // Assert
        self::assertSame($expectedResponse, $response);
    }
}
