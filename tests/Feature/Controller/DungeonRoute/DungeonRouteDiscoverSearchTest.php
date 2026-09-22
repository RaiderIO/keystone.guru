<?php

namespace Tests\Feature\Controller\DungeonRoute;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('DungeonRoute')]
final class DungeonRouteDiscoverSearchTest extends PublicTestCase
{
    #[Test]
    public function search_givenGuest_redirectsPermanentlyToDungeonRouteSearch(): void
    {
        // Arrange
        $this->actingAsGuest();

        // Act
        $response = $this->get(route('dungeonroutes.search'));

        // Assert
        $response->assertStatus(301);
        $response->assertRedirect(route('dungeon.dungeonroute.search'));
    }

    #[Test]
    public function search_givenLegacyFilterQueryString_redirectsPermanentlyToDungeonRouteSearchWithoutIt(): void
    {
        // Arrange
        $this->actingAsGuest();

        // Act
        $response = $this->get(sprintf('%s?season=1&affixgroups=2&dungeons=3&title=foo', route('dungeonroutes.search')));

        // Assert
        $response->assertStatus(301);
        $response->assertRedirect(route('dungeon.dungeonroute.search'));
    }

    #[Test]
    public function search_givenRouteDefinition_isThrottledBySearchDungeonRouteLimiter(): void
    {
        // Arrange
        $route = Route::getRoutes()->getByName('dungeonroutes.search');

        // Act
        $middleware = $route?->gatherMiddleware() ?? [];

        // Assert
        $this->assertContains('throttle:search-dungeonroute', $middleware);
    }

    #[Test]
    public function search_givenOldAjaxSearchEndpoint_hasNoGetRoute(): void
    {
        // Arrange
        $request = Request::create('/ajax/search', 'GET');

        // Act
        $matchedRoute = null;

        try {
            $matchedRoute = Route::getRoutes()->match($request);
        } catch (NotFoundHttpException|MethodNotAllowedHttpException) {
            // No GET route on this URI at all is the expected outcome
        }

        // Assert
        $this->assertTrue(
            $matchedRoute === null || $matchedRoute->isFallback,
            sprintf('GET /ajax/search still resolves to %s', $matchedRoute?->getActionName()),
        );
    }
}
