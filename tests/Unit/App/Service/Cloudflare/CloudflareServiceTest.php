<?php

namespace Tests\Unit\App\Service\Cloudflare;

use App\Service\Cloudflare\CloudflareServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\Fixtures\LoggingFixtures;
use Tests\Fixtures\ServiceFixtures;
use Tests\TestCases\PublicTestCase;

#[Group('CloudflareService')]
final class CloudflareServiceTest extends PublicTestCase
{
    private CloudflareServiceInterface|MockObject $cacheService;

    /**
     * @throws Exception
     */
    #[\Override]
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
            log: $log,
        );

        $cloudflareService->expects($this->once())
            ->method('curlGet')
            ->with($this->stringEndsWith('/ips-v4'))
            ->willReturn($response);

        // Act
        $ipRanges = $cloudflareService->getIpRangesV4(false);

        // Assert
        $this->assertCount(15, $ipRanges);
        $this->assertSame('173.245.48.0/20', $ipRanges[0]);
        $this->assertSame('131.0.72.0/22', $ipRanges[14]);
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
            log: $log,
        );

        $cloudflareService->expects($this->once())
            ->method('curlGet')
            ->with($this->stringEndsWith('/ips-v6'))
            ->willReturn($response);

        // Act
        $ipRanges = $cloudflareService->getIpRangesV6(false);

        // Assert
        $this->assertCount(7, $ipRanges);
        $this->assertSame('2400:cb00::/32', $ipRanges[0]);
        $this->assertSame('2c0f:f248::/32', $ipRanges[6]);
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
        $this->assertSame([...$ipRangesV4, ...$ipRangesV6], $ipRanges);
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
            ->method('getIpRangesInvalidIpAddress')
            ->with('error fetching ip from database');

        $cloudflareService = ServiceFixtures::getCloudflareServiceMock(
            testCase: $this,
            methodsToMock: ['curlGet'],
            cacheService: $this->cacheService,
            log: $log,
        );

        $cloudflareService->method('curlGet')
            ->willReturn($response);

        // Act
        $ipRanges = $cloudflareService->getIpRangesV4(false);

        // Assert
        $this->assertCount(14, $ipRanges);
        $this->assertNotContains('error fetching ip from database', $ipRanges);
    }

    /**
     * @throws Exception
     */
    #[Test]
    #[Group('CloudflareService')]
    public function getIpRangesV4_GivenRequest_BoundsConnectAndResponseTimeouts(): void
    {
        // Arrange
        $log = LoggingFixtures::createCloudflareServiceLogging($this);

        $cloudflareService = ServiceFixtures::getCloudflareServiceMock(
            testCase: $this,
            methodsToMock: ['curlGet'],
            cacheService: $this->cacheService,
            log: $log,
        );

        $cloudflareService->expects($this->once())
            ->method('curlGet')
            ->with(
                $this->anything(),
                $this->callback(static fn(array $options): bool => ($options[CURLOPT_CONNECTTIMEOUT] ?? null) === 5
                    && ($options[CURLOPT_TIMEOUT] ?? null) === 5),
            )
            ->willReturn($this->getResponse('ipsv4'));

        // Act
        $cloudflareService->getIpRangesV4(false);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getIpRangesV4_givenIpV6Range_rejectsTheIpV6Range(): void
    {
        // Arrange
        $log = LoggingFixtures::createCloudflareServiceLogging($this);
        $log->expects($this->once())
            ->method('getIpRangesInvalidIpAddress')
            ->with('2400:cb00::/32');

        $cloudflareService = ServiceFixtures::getCloudflareServiceMock(
            testCase: $this,
            methodsToMock: ['curlGet'],
            cacheService: $this->cacheService,
            log: $log,
        );

        $cloudflareService->method('curlGet')
            ->willReturn("173.245.48.0/20\n2400:cb00::/32\n");

        // Act
        $ipRanges = $cloudflareService->getIpRangesV4(false);

        // Assert
        $this->assertSame(['173.245.48.0/20'], $ipRanges);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getIpRangesV6_givenIpV4Range_rejectsTheIpV4Range(): void
    {
        // Arrange
        $log = LoggingFixtures::createCloudflareServiceLogging($this);
        $log->expects($this->once())
            ->method('getIpRangesInvalidIpAddress')
            ->with('173.245.48.0/20');

        $cloudflareService = ServiceFixtures::getCloudflareServiceMock(
            testCase: $this,
            methodsToMock: ['curlGet'],
            cacheService: $this->cacheService,
            log: $log,
        );

        $cloudflareService->method('curlGet')
            ->willReturn("2400:cb00::/32\n173.245.48.0/20\n");

        // Act
        $ipRanges = $cloudflareService->getIpRangesV6(false);

        // Assert
        $this->assertSame(['2400:cb00::/32'], $ipRanges);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getIpRangesV4_givenRequest_requestsTheIpV4ListUrl(): void
    {
        // Arrange
        $cloudflareService = ServiceFixtures::getCloudflareServiceMock(
            testCase: $this,
            methodsToMock: ['curlGet'],
            cacheService: $this->cacheService,
        );

        // Assert
        $cloudflareService->expects($this->once())
            ->method('curlGet')
            ->with('https://cloudflare.com/ips-v4', $this->anything())
            ->willReturn($this->getResponse('ipsv4'));

        // Act
        $cloudflareService->getIpRangesV4(false);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getIpRangesV6_givenRequest_requestsTheIpV6ListUrl(): void
    {
        // Arrange
        $cloudflareService = ServiceFixtures::getCloudflareServiceMock(
            testCase: $this,
            methodsToMock: ['curlGet'],
            cacheService: $this->cacheService,
        );

        // Assert
        $cloudflareService->expects($this->once())
            ->method('curlGet')
            ->with('https://cloudflare.com/ips-v6', $this->anything())
            ->willReturn($this->getResponse('ipsv6'));

        // Act
        $cloudflareService->getIpRangesV6(false);
    }

    /**
     * @throws Exception
     */
    #[Test]
    #[Group('CloudflareService')]
    public function getIpRanges_givenUseCache_cachesEachAddressFamilyUnderItsOwnKey(): void
    {
        // Arrange - one shared key would serve the IPv4 list as the IPv6 one, and TrustProxies would trust neither
        $cacheCalls   = [];
        $cacheService = ServiceFixtures::getCacheServiceMock($this, ['rememberWhen']);
        $cacheService->expects($this->exactly(2))
            ->method('rememberWhen')
            ->willReturnCallback(static function (bool $useCache, string $key) use (&$cacheCalls): array {
                $cacheCalls[] = [$useCache, $key];

                return [];
            });

        $cloudflareService = ServiceFixtures::getCloudflareServiceMock(
            testCase: $this,
            methodsToMock: ['curlGet'],
            cacheService: $cacheService,
        );

        // Act
        $cloudflareService->getIpRanges();

        // Assert
        $this->assertSame([
            [true, 'cloudflare:ip-ranges-v4'],
            [true, 'cloudflare:ip-ranges-v6'],
        ], $cacheCalls);
    }

    private function getResponse(string $fileName): string
    {
        return file_get_contents(sprintf('%s/Fixtures/%s.txt', __DIR__, $fileName));
    }
}
