<?php

namespace Tests\Unit\App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Middleware')]
#[Group('CsrfExemptRoutes')]
class CsrfExemptRoutesTest extends PublicTestCase
{
    #[Test]
    public function getExcludedPaths_givenBootedApplication_includesHeatmapEmbedDataRoute(): void
    {
        // Arrange
        /** @var ValidateCsrfToken $middleware */
        $middleware = $this->app->make(ValidateCsrfToken::class);

        // Act
        $excludedPaths = $middleware->getExcludedPaths();

        // Assert
        self::assertContains('ajax/heatmap/data', $excludedPaths);
    }

    #[Test]
    public function getExcludedPaths_givenBootedApplication_stillIncludesWebhookRoutes(): void
    {
        // Arrange
        /** @var ValidateCsrfToken $middleware */
        $middleware = $this->app->make(ValidateCsrfToken::class);

        // Act
        $excludedPaths = $middleware->getExcludedPaths();

        // Assert
        self::assertContains('webhook/*', $excludedPaths);
    }
}
