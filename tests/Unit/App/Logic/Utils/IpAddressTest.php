<?php

namespace Tests\Unit\App\Logic\Utils;

use App\Logic\Utils\IpAddress;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('IpAddress')]
#[Group('RateLimiting')]
final class IpAddressTest extends TestCase
{
    #[Test]
    #[DataProvider('rateLimitKey_dataProvider')]
    public function rateLimitKey_givenAddress_returnsExpectedKey(?string $ipAddress, string $expected): void
    {
        // Act
        $result = IpAddress::rateLimitKey($ipAddress);

        // Assert
        $this->assertSame($expected, $result);
    }

    /**
     * @return array<string, array{string|null, string}>
     */
    public static function rateLimitKey_dataProvider(): array
    {
        return [
            'ipv4'                      => ['203.0.113.10', '203.0.113.10'],
            'ipv4 loopback'             => ['127.0.0.1', '127.0.0.1'],
            'ipv6 full form'            => ['2001:0db8:0001:0002:0003:0004:0005:0006', '2001:db8:1:2::/64'],
            'ipv6 compressed form'      => ['2001:db8:1:2::abcd', '2001:db8:1:2::/64'],
            'ipv6 upper case'           => ['2001:DB8:1:2:FFFF:FFFF:FFFF:FFFF', '2001:db8:1:2::/64'],
            'ipv6 loopback'             => ['::1', '::/64'],
            'ipv4-mapped ipv6'          => ['::ffff:203.0.113.10', '203.0.113.10'],
            'ipv4-mapped ipv6 hex form' => ['::ffff:cb00:710a', '203.0.113.10'],
            'not an ip address'         => ['not-an-ip', 'not-an-ip'],
            'empty'                     => ['', ''],
            'null'                      => [null, ''],
        ];
    }

    #[Test]
    public function rateLimitKey_givenTwoAddressesInTheSameIpv6Network_returnsTheSameKey(): void
    {
        // Act
        $first  = IpAddress::rateLimitKey('2001:db8:1:2::1');
        $second = IpAddress::rateLimitKey('2001:db8:1:2:ffff:ffff:ffff:fffe');

        // Assert
        $this->assertSame($first, $second);
    }

    #[Test]
    public function rateLimitKey_givenAddressesInDifferentIpv6Networks_returnsDifferentKeys(): void
    {
        // Act
        $first  = IpAddress::rateLimitKey('2001:db8:1:2::1');
        $second = IpAddress::rateLimitKey('2001:db8:1:3::1');

        // Assert
        $this->assertNotSame($first, $second);
        $this->assertSame('2001:db8:1:3::/64', $second);
    }

    #[Test]
    public function rateLimitKey_givenDifferentIpv4Addresses_returnsDifferentKeys(): void
    {
        // Act
        $first  = IpAddress::rateLimitKey('203.0.113.10');
        $second = IpAddress::rateLimitKey('203.0.113.11');

        // Assert
        $this->assertNotSame($first, $second);
    }
}
