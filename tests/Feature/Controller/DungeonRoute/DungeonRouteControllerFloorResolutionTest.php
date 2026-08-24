<?php

namespace Tests\Feature\Controller\DungeonRoute;

use App\Models\CombatLog\ChallengeModeRun;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Models\PublishedState;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('DungeonRoute')]
final class DungeonRouteControllerFloorResolutionTest extends PublicTestCase
{
    use ProvidesDungeon;

    #[Test]
    public function viewFloor_givenExistingFloorIndex_returnsOk(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $route = $this->createRoute($owner);
        /** @var Floor $floor */
        $floor = Floor::where('dungeon_id', $route->dungeon_id)->defaultOrFacade($route->mappingVersion)->first();

        try {
            // Act
            $response = $this->get(route('dungeonroute.view.floor', [
                'dungeon'      => $route->dungeon,
                'dungeonroute' => $route,
                'title'        => $route->getTitleSlug(),
                'floorIndex'   => $floor->index,
            ]));

            // Assert
            $response->assertOk();
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function viewFloor_givenNonExistentFloorIndex_redirectsToDefaultFloor(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $route = $this->createRoute($owner);
        /** @var Floor $defaultFloor */
        $defaultFloor = Floor::where('dungeon_id', $route->dungeon_id)->defaultOrFacade($route->mappingVersion)->first();

        try {
            // Act
            $response = $this->get(route('dungeonroute.view.floor', [
                'dungeon'      => $route->dungeon,
                'dungeonroute' => $route,
                'title'        => $route->getTitleSlug(),
                'floorIndex'   => 999999,
            ]));

            // Assert
            $response->assertRedirect(route('dungeonroute.view.floor', [
                'dungeon'      => $route->dungeon,
                'dungeonroute' => $route,
                'title'        => $route->getTitleSlug(),
                'floorIndex'   => $defaultFloor->index,
            ]));
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function viewFloor_givenUserPrefersFacadeAndRequestsNonFacadeFloor_redirectsToFacadeFloor(): void
    {
        // Arrange
        $viewer = User::factory()->create(['map_facade_style' => User::MAP_FACADE_STYLE_FACADE]);
        $owner  = User::factory()->create();
        $route  = $this->createFacadeRoute($owner);
        /** @var Floor $nonFacadeFloor */
        $nonFacadeFloor = Floor::where('dungeon_id', $route->dungeon_id)->where('facade', 0)->where('default', 1)->firstOrFail();
        /** @var Floor $facadeFloor */
        $facadeFloor = Floor::where('dungeon_id', $route->dungeon_id)->where('facade', 1)->firstOrFail();

        try {
            $this->be($viewer);

            // Act
            $response = $this->get(route('dungeonroute.view.floor', [
                'dungeon'      => $route->dungeon,
                'dungeonroute' => $route,
                'title'        => $route->getTitleSlug(),
                'floorIndex'   => $nonFacadeFloor->index,
            ]));

            // Assert
            $response->assertRedirect(route('dungeonroute.view.floor', [
                'dungeon'      => $route->dungeon,
                'dungeonroute' => $route,
                'title'        => $route->getTitleSlug(),
                'floorIndex'   => $facadeFloor->index,
            ]));
        } finally {
            $route->delete();
            $owner->delete();
            $viewer->delete();
        }
    }

    #[Test]
    public function viewFloor_givenUserPrefersSplitFloorsAndRequestsFacadeFloor_redirectsToDefaultFloor(): void
    {
        // Arrange
        $viewer = User::factory()->create(['map_facade_style' => User::MAP_FACADE_STYLE_SPLIT_FLOORS]);
        $owner  = User::factory()->create();
        $route  = $this->createFacadeRoute($owner);
        /** @var Floor $defaultFloor */
        $defaultFloor = Floor::where('dungeon_id', $route->dungeon_id)->where('facade', 0)->where('default', 1)->firstOrFail();
        /** @var Floor $facadeFloor */
        $facadeFloor = Floor::where('dungeon_id', $route->dungeon_id)->where('facade', 1)->firstOrFail();

        try {
            $this->be($viewer);

            // Act
            $response = $this->get(route('dungeonroute.view.floor', [
                'dungeon'      => $route->dungeon,
                'dungeonroute' => $route,
                'title'        => $route->getTitleSlug(),
                'floorIndex'   => $facadeFloor->index,
            ]));

            // Assert
            $response->assertRedirect(route('dungeonroute.view.floor', [
                'dungeon'      => $route->dungeon,
                'dungeonroute' => $route,
                'title'        => $route->getTitleSlug(),
                'floorIndex'   => $defaultFloor->index,
            ]));
        } finally {
            $route->delete();
            $owner->delete();
            $viewer->delete();
        }
    }

    #[Test]
    public function presentFloor_givenExistingFloorIndex_returnsOk(): void
    {
        // Arrange
        $owner              = User::factory()->create();
        $route              = $this->createRoute($owner);
        $challengeModeRunId = ChallengeModeRun::create(['dungeon_route_id' => $route->id])->id;
        /** @var Floor $floor */
        $floor = Floor::where('dungeon_id', $route->dungeon_id)->defaultOrFacade($route->mappingVersion)->first();

        try {
            // Act
            $response = $this->get(route('dungeonroute.present.floor', [
                'dungeon'      => $route->dungeon,
                'dungeonroute' => $route,
                'title'        => $route->getTitleSlug(),
                'floorIndex'   => $floor->index,
            ]));

            // Assert
            $response->assertOk();
        } finally {
            ChallengeModeRun::query()->whereKey($challengeModeRunId)->delete();
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function presentFloor_givenNonExistentFloorIndex_redirectsToDefaultFloor(): void
    {
        // Arrange
        $owner              = User::factory()->create();
        $route              = $this->createRoute($owner);
        $challengeModeRunId = ChallengeModeRun::create(['dungeon_route_id' => $route->id])->id;
        /** @var Floor $defaultFloor */
        $defaultFloor = Floor::where('dungeon_id', $route->dungeon_id)->defaultOrFacade($route->mappingVersion)->first();

        try {
            // Act
            $response = $this->get(route('dungeonroute.present.floor', [
                'dungeon'      => $route->dungeon,
                'dungeonroute' => $route,
                'title'        => $route->getTitleSlug(),
                'floorIndex'   => 999999,
            ]));

            // Assert
            $response->assertRedirect(route('dungeonroute.present.floor', [
                'dungeon'      => $route->dungeon,
                'dungeonroute' => $route,
                'title'        => $route->getTitleSlug(),
                'floorIndex'   => $defaultFloor->index,
            ]));
        } finally {
            ChallengeModeRun::query()->whereKey($challengeModeRunId)->delete();
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function editFloor_givenExistingFloorIndex_returnsOk(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $route = $this->createRoute($owner);
        /** @var Floor $floor */
        $floor = Floor::where('dungeon_id', $route->dungeon_id)->defaultOrFacade($route->mappingVersion)->first();

        try {
            $this->be($owner);

            // Act
            $response = $this->get(route('dungeonroute.edit.floor', [
                'dungeon'      => $route->dungeon,
                'dungeonroute' => $route,
                'title'        => $route->getTitleSlug(),
                'floorIndex'   => $floor->index,
            ]));

            // Assert
            $response->assertOk();
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function editFloor_givenNonExistentFloorIndex_redirectsToDefaultFloor(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $route = $this->createRoute($owner);
        /** @var Floor $defaultFloor */
        $defaultFloor = Floor::where('dungeon_id', $route->dungeon_id)->defaultOrFacade($route->mappingVersion)->first();

        try {
            $this->be($owner);

            // Act
            $response = $this->get(route('dungeonroute.edit.floor', [
                'dungeon'      => $route->dungeon,
                'dungeonroute' => $route,
                'title'        => $route->getTitleSlug(),
                'floorIndex'   => 999999,
            ]));

            // Assert
            $response->assertRedirect(route('dungeonroute.edit.floor', [
                'dungeon'      => $route->dungeon,
                'dungeonroute' => $route,
                'title'        => $route->getTitleSlug(),
                'floorIndex'   => $defaultFloor->index,
            ]));
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    /**
     * embed()'s argument-shift bug is fixed (#4266) and its floor resolution now goes through
     * FloorResolutionService like the other methods in this file. Unlike viewFloor/presentFloor/
     * editFloor, embed() has no redirect branch - a non-existent floorIndex falls back to rendering
     * the default floor directly rather than redirecting to it.
     */
    #[Test]
    public function embed_givenNonExistentFloorIndex_rendersDefaultFloor(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $route = $this->createRoute($owner);
        /** @var Floor $defaultFloor */
        $defaultFloor = Floor::where('dungeon_id', $route->dungeon_id)->defaultOrFacade($route->mappingVersion)->first();

        try {
            // Act
            $response = $this->get(route('dungeonroute.embed.floor', [
                'dungeon'      => $route->dungeon,
                'dungeonroute' => $route,
                'title'        => $route->getTitleSlug(),
                'floorIndex'   => 999999,
            ]));

            // Assert - embed() has no redirect branch, so the default floor is rendered directly
            $response->assertOk();
            $response->assertViewHas('floor', static fn(Floor $viewFloor) => $viewFloor->id === $defaultFloor->id);
            $response->assertViewHas('dungeonroute', static fn(DungeonRoute $viewRoute) => $viewRoute->id === $route->id);
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    /**
     * A published, non-sandbox route owned by $owner. Sandbox routes (which the factory creates by
     * default) are editable by anyone, so expires_at must be null here.
     */
    private function createRoute(User $owner): DungeonRoute
    {
        // facadeEnabled: false - guests default to the facade map style (User::DEFAULT_MAP_FACADE_STYLE),
        // which would otherwise collapse floor selection and make these tests non-deterministic
        [$dungeon, $mappingVersion] = $this->findDungeon(facadeEnabled: false, minActiveFloors: 1, requireDefaultFloor: true);

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

    /**
     * A published, non-sandbox route on a dungeon whose current mapping version has facade
     * rendering enabled and a default (non-facade) floor, so the facade-preference redirect tests
     * can exercise both directions.
     */
    private function createFacadeRoute(User $owner): DungeonRoute
    {
        [$dungeon, $mappingVersion] = $this->findDungeon(facadeEnabled: true, minActiveFloors: 1, requireDefaultFloor: true);

        return DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
            'author_id'          => $owner->id,
            'expires_at'         => null,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ]);
    }
}
