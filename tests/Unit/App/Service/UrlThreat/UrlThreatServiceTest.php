<?php

namespace Tests\Unit\App\Service\UrlThreat;

use App\Service\UrlThreat\Logging\UrlThreatServiceLoggingInterface;
use App\Service\UrlThreat\UrlThreatService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('UrlThreat')]
final class UrlThreatServiceTest extends PublicTestCase
{
    private const string DNS_QUERY_URL = 'security.cloudflare-dns.com/dns-query*';

    #[Test]
    public function isThreat_givenAHostCloudflareBlocks_returnsTrue(): void
    {
        // Arrange
        config(['services.url_threat_check.enabled' => true]);
        Http::fake([
            self::DNS_QUERY_URL => Http::response($this->dnsAnswer('phishing.example.com', ['0.0.0.0'])),
        ]);

        // Act
        $result = $this->createService()->isThreat('https://phishing.example.com/login?next=/');

        // Assert
        $this->assertTrue($result);
        Http::assertSent(static fn(Request $request): bool => $request->hasHeader('Accept', 'application/dns-json') &&
            str_starts_with($request->url(), 'https://security.cloudflare-dns.com/dns-query?') &&
            $request['name'] === 'phishing.example.com' &&
            $request['type'] === 'A');
    }

    #[Test]
    public function isThreat_givenAHostCloudflareResolves_returnsFalse(): void
    {
        // Arrange
        config(['services.url_threat_check.enabled' => true]);
        Http::fake([
            self::DNS_QUERY_URL => Http::response($this->dnsAnswer('example.com', ['93.184.215.14'])),
        ]);

        // Act
        $result = $this->createService()->isThreat('https://example.com');

        // Assert
        $this->assertFalse($result);
        Http::assertSentCount(1);
    }

    #[Test]
    public function isThreat_givenAHostThatDoesNotExist_returnsFalse(): void
    {
        // Arrange
        config(['services.url_threat_check.enabled' => true]);
        Http::fake([
            self::DNS_QUERY_URL => Http::response(['Status' => 3, 'Question' => [['name' => 'missing.example.com', 'type' => 1]]]),
        ]);

        // Act
        $result = $this->createService()->isThreat('https://missing.example.com');

        // Assert
        $this->assertFalse($result);
        Http::assertSentCount(1);
    }

    #[Test]
    public function isThreat_givenTheCheckIsDisabled_returnsFalseWithoutALookup(): void
    {
        // Arrange
        config(['services.url_threat_check.enabled' => false]);
        Http::fake([
            self::DNS_QUERY_URL => Http::response($this->dnsAnswer('phishing.example.com', ['0.0.0.0'])),
        ]);

        // Act
        $result = $this->createService()->isThreat('https://phishing.example.com/login');

        // Assert
        $this->assertFalse($result);
        Http::assertNothingSent();
    }

    #[Test]
    public function isThreat_givenAnIpAddressHost_returnsFalseWithoutALookup(): void
    {
        // Arrange
        config(['services.url_threat_check.enabled' => true]);
        Http::fake([
            self::DNS_QUERY_URL => Http::response($this->dnsAnswer('192.0.2.1', ['0.0.0.0'])),
        ]);

        // Act
        $result = $this->createService()->isThreat('https://192.0.2.1/login');

        // Assert
        $this->assertFalse($result);
        Http::assertNothingSent();
    }

    #[Test]
    public function isThreat_givenAnErrorResponse_returnsFalseAndLogsIt(): void
    {
        // Arrange
        config(['services.url_threat_check.enabled' => true]);
        Http::fake([
            self::DNS_QUERY_URL => Http::response('', 503),
        ]);
        $log = $this->createMock(UrlThreatServiceLoggingInterface::class);
        $log->expects($this->once())->method('isThreatInvalidResponse')->with('https://example.com', 503);

        // Act
        $result = new UrlThreatService($log)->isThreat('https://example.com');

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function isThreat_givenAConnectionFailure_returnsFalseAndLogsIt(): void
    {
        // Arrange
        config(['services.url_threat_check.enabled' => true]);
        Http::fake(static fn() => throw new ConnectionException('Connection timed out'));
        $log = $this->createMock(UrlThreatServiceLoggingInterface::class);
        $log->expects($this->once())->method('isThreatRequestFailed')->with('https://example.com', 'Connection timed out');

        // Act
        $result = new UrlThreatService($log)->isThreat('https://example.com');

        // Assert
        $this->assertFalse($result);
    }

    private function createService(): UrlThreatService
    {
        return new UrlThreatService($this->createStub(UrlThreatServiceLoggingInterface::class));
    }

    /**
     * @param  array<int, string>   $addresses
     * @return array<string, mixed>
     */
    private function dnsAnswer(string $host, array $addresses): array
    {
        return [
            'Status'   => 0,
            'Question' => [['name' => $host, 'type' => 1]],
            'Answer'   => array_map(static fn(string $address): array => ['name' => $host, 'type' => 1, 'TTL' => 60, 'data' => $address], $addresses),
        ];
    }
}
