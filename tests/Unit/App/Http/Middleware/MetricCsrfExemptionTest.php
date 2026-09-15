<?php

namespace Tests\Unit\App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCases\PublicTestCase;

/**
 * Asserts on the middleware's exemption list directly: Laravel skips CSRF verification entirely under the
 * testing environment (runningUnitTests()), so a plain feature POST without a token would pass regardless.
 */
#[Group('Middleware')]
#[Group('Metric')]
#[Group('MetricCsrfExemption')]
class MetricCsrfExemptionTest extends PublicTestCase
{
    #[Test]
    public function getExcludedPaths_givenBootedApplication_includesMetricPrefixAndWebhooksOnly(): void
    {
        // Arrange
        /** @var ValidateCsrfToken $middleware */
        $middleware = $this->app->make(ValidateCsrfToken::class);

        // Act
        $excludedPaths = $middleware->getExcludedPaths();

        // Assert
        self::assertContains('ajax/metric', $excludedPaths);
        self::assertContains('ajax/metric/*', $excludedPaths);
        self::assertContains('webhook/*', $excludedPaths);
        self::assertNotContains('ajax/heatmap/data', $excludedPaths);
    }

    #[Test]
    #[DataProvider('metricRequestProvider')]
    public function inExceptArray_givenPostRequest_returnsWhetherPathIsExempt(string $uri, bool $expected): void
    {
        // Arrange
        /** @var ValidateCsrfToken $middleware */
        $middleware = $this->app->make(ValidateCsrfToken::class);
        $request    = Request::create($uri, 'POST');
        $method     = new ReflectionMethod($middleware, 'inExceptArray');

        // Act
        $result = $method->invoke($middleware, $request);

        // Assert
        self::assertSame($expected, $result);
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function metricRequestProvider(): array
    {
        return [
            'dungeon route metric' => ['/ajax/metric/route/abcdefgh', true],
            'generic metric'       => ['/ajax/metric', true],
            'other ajax post'      => ['/ajax/some-other-post', false],
        ];
    }
}
