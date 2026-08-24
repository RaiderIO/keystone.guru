<?php

namespace Tests\Feature\Controller\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Models\PublishedState;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

/**
 * embed() previously never declared a `Dungeon $dungeon` parameter even though `{dungeon}` is part
 * of its route (`/route/{dungeon}/{dungeonroute}/{title?}/embed/{floorIndex}`), so Laravel's
 * positional dependency resolution silently shifted the raw route values into the wrong untyped
 * parameters and {floorIndex} was always dropped in favour of floor 1 (#4266).
 */
#[Group('Controller')]
#[Group('DungeonRoute')]
final class DungeonRouteControllerEmbedTest extends PublicTestCase
{
    use ProvidesDungeon;

    #[Test]
    public function embed_givenNoFloorIndex_rendersDefaultFloor(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $route = $this->createRoute($owner);
        /** @var Floor $floorOne */
        $floorOne = $route->dungeon->floors()->where('facade', 0)->where('index', 1)->firstOrFail();

        try {
            // Act
            $response = $this->get(route('dungeonroute.embed', [
                'dungeon'      => $route->dungeon,
                'dungeonroute' => $route,
                'title'        => $route->getTitleSlug(),
            ]));

            // Assert
            $response->assertOk();
            $response->assertViewHas('floor', static fn(Floor $viewFloor) => $viewFloor->id === $floorOne->id);
            $response->assertViewHas('dungeonroute', static fn(DungeonRoute $viewRoute) => $viewRoute->id === $route->id);
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function embed_givenExistingNonDefaultFloorIndex_rendersRequestedFloor(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $route = $this->createRoute($owner);
        /** @var Floor $otherFloor */
        $otherFloor = $route->dungeon->floors()->where('facade', 0)->where('index', '!=', 1)->firstOrFail();

        try {
            // Act - request an explicit, existing, non-1 floor index
            $response = $this->get(route('dungeonroute.embed.floor', [
                'dungeon'      => $route->dungeon,
                'dungeonroute' => $route,
                'title'        => $route->getTitleSlug(),
                'floorIndex'   => $otherFloor->index,
            ]));

            // Assert - the requested floor is honored, not floor 1
            $response->assertOk();
            $response->assertViewHas('floor', static fn(Floor $viewFloor) => $viewFloor->id === $otherFloor->id);
            $response->assertViewHas('dungeonroute', static fn(DungeonRoute $viewRoute) => $viewRoute->id === $route->id);
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function embed_givenNonExistentDungeonroutePublicKey_returnsNotFound(): void
    {
        // Act
        $response = $this->get('/route/some-dungeon/does-not-exist-as-a-public-key/some-title/embed/1');

        // Assert
        $response->assertNotFound();
    }

    /**
     * A published, non-sandbox route owned by $owner. Sandbox routes (which the factory creates by
     * default) are editable by anyone, so expires_at must be null here.
     */
    private function createRoute(User $owner): DungeonRoute
    {
        // facadeEnabled: false - guests default to the facade map style (User::DEFAULT_MAP_FACADE_STYLE),
        // which would otherwise collapse floor selection and make these tests non-deterministic
        [$dungeon, $mappingVersion] = $this->findDungeon(facadeEnabled: false, minActiveFloors: 2, requireDefaultFloor: true);

        return DungeonRoute::factory()->create([
            'dungeon_id' => $dungeon->id,
            // The factory's own definition() picks a random dungeon and derives mapping_version_id
            // from it - overriding dungeon_id alone leaves mapping_version_id pointing at that other
            // dungeon's mapping version, so it must be overridden here too.
            'mapping_version_id' => $mappingVersion->id,
            'author_id'          => $owner->id,
            'expires_at'         => null,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ]);
    }
}
