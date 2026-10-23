<?php

namespace Tests\Unit\App\Service\WebRisk;

use App\Service\WebRisk\Logging\WebRiskServiceLoggingInterface;
use App\Service\WebRisk\WebRiskService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('WebRisk')]
final class WebRiskServiceTest extends PublicTestCase
{
    private const string API_KEY = 'test-web-risk-key';

    #[Test]
    public function isThreat_givenAUrlGoogleLists_returnsTrue(): void
    {
        // Arrange
        config(['services.google_web_risk.api_key' => self::API_KEY]);
        Http::fake([
            'webrisk.googleapis.com/*' => Http::response(['threat' => ['threatTypes' => ['SOCIAL_ENGINEERING'], 'expireTime' => '2026-10-24T00:00:00Z']]),
        ]);

        // Act
        $result = $this->createService()->isThreat('https://phishing.example.com/login');

        // Assert
        $this->assertTrue($result);
        Http::assertSent(static fn(Request $request): bool => $request->hasHeader('X-Goog-Api-Key', self::API_KEY) &&
            str_starts_with($request->url(), 'https://webrisk.googleapis.com/v1/uris:search?') &&
            str_contains($request->url(), sprintf('uri=%s', urlencode('https://phishing.example.com/login'))) &&
            str_contains($request->url(), 'threatTypes=MALWARE&threatTypes=SOCIAL_ENGINEERING&threatTypes=UNWANTED_SOFTWARE') &&
            !str_contains($request->url(), self::API_KEY));
    }

    #[Test]
    public function isThreat_givenAUrlGoogleDoesNotList_returnsFalse(): void
    {
        // Arrange
        config(['services.google_web_risk.api_key' => self::API_KEY]);
        Http::fake([
            'webrisk.googleapis.com/*' => Http::response([]),
        ]);

        // Act
        $result = $this->createService()->isThreat('https://example.com');

        // Assert
        $this->assertFalse($result);
        Http::assertSentCount(1);
    }

    #[Test]
    public function isThreat_givenNoApiKey_returnsFalseWithoutALookup(): void
    {
        // Arrange
        config(['services.google_web_risk.api_key' => null]);
        Http::fake([
            'webrisk.googleapis.com/*' => Http::response(['threat' => ['threatTypes' => ['MALWARE']]]),
        ]);

        // Act
        $result = $this->createService()->isThreat('https://phishing.example.com/login');

        // Assert
        $this->assertFalse($result);
        Http::assertNothingSent();
    }

    #[Test]
    public function isThreat_givenAnErrorResponse_returnsFalseAndLogsIt(): void
    {
        // Arrange
        config(['services.google_web_risk.api_key' => self::API_KEY]);
        Http::fake([
            'webrisk.googleapis.com/*' => Http::response(['error' => ['code' => 403]], 403),
        ]);
        $log = $this->createMock(WebRiskServiceLoggingInterface::class);
        $log->expects($this->once())->method('isThreatInvalidResponse')->with('https://example.com', 403);

        // Act
        $result = new WebRiskService($log)->isThreat('https://example.com');

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function isThreat_givenAConnectionFailure_returnsFalseAndLogsIt(): void
    {
        // Arrange
        config(['services.google_web_risk.api_key' => self::API_KEY]);
        Http::fake(static fn() => throw new ConnectionException('Connection timed out'));
        $log = $this->createMock(WebRiskServiceLoggingInterface::class);
        $log->expects($this->once())->method('isThreatRequestFailed')->with('https://example.com', 'Connection timed out');

        // Act
        $result = new WebRiskService($log)->isThreat('https://example.com');

        // Assert
        $this->assertFalse($result);
    }

    private function createService(): WebRiskService
    {
        return new WebRiskService($this->createStub(WebRiskServiceLoggingInterface::class));
    }
}
