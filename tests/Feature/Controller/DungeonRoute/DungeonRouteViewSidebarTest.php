<?php

namespace Tests\Feature\Controller\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\PublishedState;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Covers the map sidebar's authorization-driven controls, which moved from direct model calls
 * ($dungeonroute->mayUserEdit(Auth::user()), ->isOwnedByUser()) to @can/@cannot directives.
 * Rendering the real Blade is the only way to catch a directive typo or an inverted condition.
 */
#[Group('Controller')]
#[Group('DungeonRoute')]
final class DungeonRouteViewSidebarTest extends PublicTestCase
{
    /**
     * Users 3 and 4 are seeded with the "user" role and are not admins - an admin may edit every
     * route, which would make the deny case pass vacuously.
     */
    private const int ROUTE_OWNER_USER_ID = 3;

    private const int OTHER_USER_ID = 4;

    #[Test]
    public function view_givenOwner_rendersTheEditButton(): void
    {
        // Arrange
        $route = $this->createRoute();

        try {
            $this->be(User::findOrFail(self::ROUTE_OWNER_USER_ID));

            // Act
            $response = $this->followingRedirects()->get($this->viewUrl($route));

            // Assert
            $response->assertOk();
            $response->assertSee(route('dungeonroute.edit', [
                'dungeon'      => $route->dungeon,
                'dungeonroute' => $route,
                'title'        => $route->getTitleSlug(),
            ]), false);
        } finally {
            $route->delete();
        }
    }

    #[Test]
    public function view_givenNonOwner_hidesTheEditButton(): void
    {
        // Arrange
        $route = $this->createRoute();

        try {
            $this->be(User::findOrFail(self::OTHER_USER_ID));

            // Act
            $response = $this->followingRedirects()->get($this->viewUrl($route));

            // Assert
            $response->assertOk();
            $response->assertDontSee(route('dungeonroute.edit', [
                'dungeon'      => $route->dungeon,
                'dungeonroute' => $route,
                'title'        => $route->getTitleSlug(),
            ]), false);
        } finally {
            $route->delete();
        }
    }

    #[Test]
    public function view_givenOwner_showsTheCannotRateOwnRouteNotice(): void
    {
        // Arrange
        $route = $this->createRoute();

        try {
            $this->be(User::findOrFail(self::ROUTE_OWNER_USER_ID));

            // Act
            $response = $this->followingRedirects()->get($this->viewUrl($route));

            // Assert
            $response->assertOk();
            $response->assertSee(__('view_common.maps.controls.elements.rating.unable_to_rate_own_route'));
        } finally {
            $route->delete();
        }
    }

    #[Test]
    public function view_givenNonOwner_showsTheRatingOptions(): void
    {
        // Arrange
        $route = $this->createRoute();

        try {
            $this->be(User::findOrFail(self::OTHER_USER_ID));

            // Act
            $response = $this->followingRedirects()->get($this->viewUrl($route));

            // Assert
            $response->assertOk();
            $response->assertDontSee(__('view_common.maps.controls.elements.rating.unable_to_rate_own_route'));
            $response->assertSee(__('view_common.maps.controls.elements.rating.your_rating'));
        } finally {
            $route->delete();
        }
    }

    /**
     * A published, non-sandbox route owned by ROUTE_OWNER_USER_ID. Sandbox routes (which the
     * factory creates by default) are editable by anyone, so expires_at must be null here.
     */
    private function createRoute(): DungeonRoute
    {
        return DungeonRoute::factory()->create([
            'author_id'          => self::ROUTE_OWNER_USER_ID,
            'expires_at'         => null,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ]);
    }

    /**
     * dungeonroute.view always redirects to the default floor, so tests follow redirects.
     */
    private function viewUrl(DungeonRoute $route): string
    {
        return route('dungeonroute.view', [
            'dungeon'      => $route->dungeon,
            'dungeonroute' => $route,
            'title'        => $route->getTitleSlug(),
        ]);
    }
}
