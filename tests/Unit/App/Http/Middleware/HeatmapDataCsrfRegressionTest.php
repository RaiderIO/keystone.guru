<?php

namespace Tests\Unit\App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Guards the proper fix for #3836: ajax.heatmap.data is a GET route (structurally outside CSRF
 * verification, safe to call from the cross-site heatmap embed <iframe>), and the temporary
 * hotfix CSRF exemption for it must not quietly come back.
 */
#[Group('Middleware')]
#[Group('HeatmapDataCsrfRegression')]
class HeatmapDataCsrfRegressionTest extends PublicTestCase
{
    #[Test]
    public function getExcludedPaths_givenBootedApplication_doesNotIncludeHeatmapDataRoute(): void
    {
        // Arrange
        /** @var ValidateCsrfToken $middleware */
        $middleware = $this->app->make(ValidateCsrfToken::class);

        // Act
        $excludedPaths = $middleware->getExcludedPaths();

        // Assert
        self::assertNotContains('ajax/heatmap/data', $excludedPaths);
        self::assertEquals(['webhook/*'], $excludedPaths);
    }

    #[Test]
    public function getData_givenPostRequest_returnsMethodNotAllowed(): void
    {
        // Arrange

        // Act
        $response = $this->post(route('ajax.heatmap.data'));

        // Assert
        $response->assertMethodNotAllowed();
    }
}
