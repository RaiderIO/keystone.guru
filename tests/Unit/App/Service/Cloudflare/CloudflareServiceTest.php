<?php

namespace Tests\Unit\App\Service\Cloudflare;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\Fixtures\LoggingFixtures;
use Tests\Fixtures\ServiceFixtures;
use Tests\TestCases\PublicTestCase;

final class CloudflareServiceTest extends PublicTestCase
{
    private CloudflareServiceInterface|MockObject $cacheService;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Always return the callback value - don't do anything with cache
        $this->cacheService = ServiceFixtures::getCacheServiceMock($this, ['rememberWhen']);
        $this->cacheService->method('rememberWhen')
            ->willReturnCallback(fn($useCache, $key, $callback) => $callback());
    }

    /**
     * @throws Exception
     */
    #[Test]
    #[Group('CloudflareService')]
    public function getIpRangesV4_GivenNormalResponse_ShouldReturnIpV4Addresses(): void
    {
        // Arrange
        $response = $this->getResponse('ipsv4');

        $log = LoggingFixtures::createCloudflareServiceLogging($this);
        $log->expects($this->never())
            ->method('getIpRangesInvalidIpAddress');

        $cloudflareService = ServiceFixtures::getCloudflareServiceMock(
            testCase: $this,
            methodsToMock: ['curlGet'],
            cacheService: $this->cacheService,
            log: $log
        );

        $cloudflareService->method('curlGet')
            ->willReturn($response);

        // Act
        $ipRanges = $cloudflareService->getIpRangesV4(false);

        // Assert
        $this->assertCount(15, $ipRanges);
    }

    /**
     * @throws Exception
     */
    #[Test]
    #[Group('CloudflareService')]
    public function getIpRangesV6_GivenNormalResponse_ShouldReturnIpV4Addresses(): void
    {
        // Arrange
        $response = $this->getResponse('ipsv6');

        $log = LoggingFixtures::createCloudflareServiceLogging($this);
        $log->expects($this->never())
            ->method('getIpRangesInvalidIpAddress');

        $cloudflareService = ServiceFixtures::getCloudflareServiceMock(
            testCase: $this,
            methodsToMock: ['curlGet'],
            cacheService: $this->cacheService,
            log: $log
        );

        $cloudflareService->method('curlGet')
            ->willReturn($response);

        // Act
        $ipRanges = $cloudflareService->getIpRangesV6(false);

        // Assert
        $this->assertCount(7, $ipRanges);
    }

    /**
     * @throws Exception
     */
    #[Test]
    #[Group('CloudflareService')]
    public function getIpRanges_GivenNormalResponse_ShouldReturnAllAddresses(): void
    {
        // Arrange
        $ipRangesV4 = [
            '173.245.48.0/20',
            '103.21.244.0/22',
        ];
        $ipRangesV6 = [
            '2400:cb00::/32',
            '2606:4700::/32',
        ];

        $cloudflareService = ServiceFixtures::getCloudflareServiceMock(
            testCase: $this,
            methodsToMock: ['getIpRangesV4', 'getIpRangesV6'],
        );
        $cloudflareService->method('getIpRangesV4')
            ->willReturn($ipRangesV4);
        $cloudflareService->method('getIpRangesV6')
            ->willReturn($ipRangesV6);

        // Act
        $ipRanges = $cloudflareService->getIpRanges();

        // Assert
        $this->assertCount(count($ipRangesV4) + count($ipRangesV6), $ipRanges);
    }

    /**
     * @throws Exception
     */
    #[Test]
    #[Group('CloudflareService')]
    public function getIpRangesV4_GivenInvalidResponse_ShouldReturnOnlyValidIpV4Addresses(): void
    {
        // Arrange
        $response = $this->getResponse('ipsv4_invalid_ip');

        $log = LoggingFixtures::createCloudflareServiceLogging($this);
        $log->expects($this->once())
            ->method('getIpRangesInvalidIpAddress');

        $cloudflareService = ServiceFixtures::getCloudflareServiceMock(
            testCase: $this,
            methodsToMock: ['curlGet'],
            cacheService: $this->cacheService,
            log: $log
        );

        $cloudflareService->method('curlGet')
            ->willReturn($response);

        // Act
        $ipRanges = $cloudflareService->getIpRangesV4(false);

        // Assert
        $this->assertCount(14, $ipRanges);
    }

    private function getResponse(string $fileName): string
    {
        return file_get_contents(sprintf('%s/Fixtures/%s.txt', __DIR__, $fileName));
    }
}
