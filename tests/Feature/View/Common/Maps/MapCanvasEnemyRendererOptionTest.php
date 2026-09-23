<?php

namespace Tests\Feature\View\Common\Maps;

use App\Features\CanvasEnemyRenderer;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Models\PublishedState;
use App\Models\User;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

/**
 * The map JS draws enemies on canvas only when the page hands it `canvasEnemyRenderer: true`, which
 * only the real Blade render can prove: read-only pages that opted in, with the feature active.
 */
#[Group('View')]
#[Group('CanvasEnemyRenderer')]
final class MapCanvasEnemyRendererOptionTest extends PublicTestCase
{
    use ProvidesDungeon;

    private const string OPTION_ON  = '"canvasEnemyRenderer":true';
    private const string OPTION_OFF = '"canvasEnemyRenderer":false';

    #[Test]
    public function explore_givenFeatureActive_enablesCanvasEnemyRenderer(): void
    {
        // Arrange
        $user = User::factory()->create();
        Feature::for($user)->activate(CanvasEnemyRenderer::class);

        try {
            // Act
            $response = $this->actingAs($user)->get($this->exploreUrl());

            // Assert
            $response->assertOk();
            $response->assertSee(self::OPTION_ON, false);
        } finally {
            Feature::for($user)->forget(CanvasEnemyRenderer::class);
            $user->delete();
        }
    }

    #[Test]
    public function explore_givenFeatureInactive_keepsDomEnemies(): void
    {
        // Arrange
        $user = User::factory()->create();
        Feature::for($user)->deactivate(CanvasEnemyRenderer::class);

        try {
            // Act
            $response = $this->actingAs($user)->get($this->exploreUrl());

            // Assert
            $response->assertOk();
            $response->assertSee(self::OPTION_OFF, false);
        } finally {
            Feature::for($user)->forget(CanvasEnemyRenderer::class);
            $user->delete();
        }
    }

    #[Test]
    public function explore_givenGuest_keepsDomEnemies(): void
    {
        // Act
        $response = $this->get($this->exploreUrl());

        // Assert
        $response->assertOk();
        $response->assertSee(self::OPTION_OFF, false);
    }

    #[Test]
    public function view_givenFeatureActive_enablesCanvasEnemyRenderer(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $route = $this->createRoute($owner);
        Feature::for($owner)->activate(CanvasEnemyRenderer::class);

        try {
            // Act
            $response = $this->actingAs($owner)->followingRedirects()->get($this->routeUrl('dungeonroute.view', $route));

            // Assert
            $response->assertOk();
            $response->assertSee(self::OPTION_ON, false);
        } finally {
            Feature::for($owner)->forget(CanvasEnemyRenderer::class);
            $route->delete();
            $owner->delete();
        }
    }

    /**
     * Editing needs real markers (dragging, the edit toolbar), so an edit page never opts in.
     */
    #[Test]
    public function edit_givenFeatureActive_keepsDomEnemies(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $route = $this->createRoute($owner);
        Feature::for($owner)->activate(CanvasEnemyRenderer::class);

        try {
            // Act
            $response = $this->actingAs($owner)->followingRedirects()->get($this->routeUrl('dungeonroute.edit', $route));

            // Assert
            $response->assertOk();
            $response->assertSee(self::OPTION_OFF, false);
            $response->assertDontSee(self::OPTION_ON, false);
        } finally {
            Feature::for($owner)->forget(CanvasEnemyRenderer::class);
            $route->delete();
            $owner->delete();
        }
    }

    private function exploreUrl(): string
    {
        [$dungeon, $mappingVersion] = $this->findDungeon(facadeEnabled: false, dungeonActive: true, requireDefaultFloor: true);
        /** @var Floor $floor */
        $floor = Floor::where('dungeon_id', $dungeon->id)->defaultOrFacade($mappingVersion)->first();

        return route('dungeon.explore.gameversion.view.floor', [
            'gameVersion' => $mappingVersion->gameVersion,
            'dungeon'     => $dungeon,
            'floorIndex'  => $floor->index,
        ]);
    }

    /**
     * A published, non-sandbox route owned by $owner, so only the owner may edit it.
     */
    private function createRoute(User $owner): DungeonRoute
    {
        return DungeonRoute::factory()->create([
            'author_id'          => $owner->id,
            'expires_at'         => null,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ]);
    }

    private function routeUrl(string $routeName, DungeonRoute $route): string
    {
        return route($routeName, [
            'dungeon'      => $route->dungeon,
            'dungeonroute' => $route,
            'title'        => $route->getTitleSlug(),
        ]);
    }
}
