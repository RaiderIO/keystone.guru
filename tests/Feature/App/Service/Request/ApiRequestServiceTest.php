<?php

namespace Tests\Feature\App\Service\Request;

use App\Service\Request\ApiRequestService;
use App\Service\Request\ApiRequestServiceInterface;
use Generator;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('ApiRequestService')]
final class ApiRequestServiceTest extends PublicTestCase
{
    #[Test]
    public function apiRequestServiceInterface_givenTheContainer_resolvesToApiRequestService(): void
    {
        // Act - both call sites (ViewService by constructor injection, Handler by resolving it in
        // shouldReturnJson()) go through the container, so a missing binding breaks both
        $apiRequestService = app(ApiRequestServiceInterface::class);

        // Assert
        $this->assertInstanceOf(ApiRequestService::class, $apiRequestService);
    }

    #[Test]
    #[DataProvider('isApiRequestPath_givenApiSurfacePath_returnsTrue_dataProvider')]
    public function isApiRequestPath_givenApiSurfacePath_returnsTrue(string $pathInfo): void
    {
        // Arrange
        $apiRequestService = new ApiRequestService();

        // Act
        $result = $apiRequestService->isApiRequestPath($pathInfo);

        // Assert
        $this->assertTrue($result);
    }

    public static function isApiRequestPath_givenApiSurfacePath_returnsTrue_dataProvider(): Generator
    {
        yield ['/ajax/brushline'];
        yield ['/ajax/profile/adfree/1'];

        yield ['/api/v1/dungeon'];
        yield ['/api/v1/route'];

        // A bare '/api/' with nothing behind it - the exact shape the hand-rolled check this
        // service replaces missed, because Request::decodedPath() trimmed it to "api" (#3903)
        yield ['/api/'];
        yield ['/ajax/'];

        // '/benchmark' is a single route rather than a prefixed group, so it carries no trailing
        // slash - the hand-rolled check never covered it at all
        yield ['/benchmark'];

        // The whitelisted /ajax/ paths ARE API requests; that they additionally render a view is a
        // view-layer concern owned by ViewService::shouldLoadViewVariables(), not by this service
        yield ['/ajax/search'];
        yield ['/ajax/dungeonroute/search'];
        yield ['/ajax/view'];
    }

    #[Test]
    #[DataProvider('isApiRequestPath_givenNonApiPath_returnsFalse_dataProvider')]
    public function isApiRequestPath_givenNonApiPath_returnsFalse(string $pathInfo): void
    {
        // Arrange
        $apiRequestService = new ApiRequestService();

        // Act
        $result = $apiRequestService->isApiRequestPath($pathInfo);

        // Assert
        $this->assertFalse($result);
    }

    public static function isApiRequestPath_givenNonApiPath_returnsFalse_dataProvider(): Generator
    {
        // The trailing slash on the '/api/' prefix is load bearing: these must all stay outside the
        // API surface, so '/api' without a trailing segment is a normal page that 404s in HTML
        yield ['/api'];
        yield ['/api.zip'];
        yield ['/apis/controllers/users.js'];
        yield ['/api_keys.json'];

        yield ['/'];
        yield ['/misc/legal'];
    }

    #[Test]
    public function isApiRequest_givenRequestWithTrailingSlashApiPath_returnsTrue(): void
    {
        // Arrange - Request::create() keeps the trailing slash that Request::decodedPath() would trim
        $apiRequestService = new ApiRequestService();

        // Act
        $result = $apiRequestService->isApiRequest(Request::create('/api/', 'GET'));

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function isApiRequest_givenRequestWithRegularPagePath_returnsFalse(): void
    {
        // Arrange
        $apiRequestService = new ApiRequestService();

        // Act
        $result = $apiRequestService->isApiRequest(Request::create('/misc/legal', 'GET'));

        // Assert
        $this->assertFalse($result);
    }
}
