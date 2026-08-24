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
     * embed() has a pre-existing argument-shift bug (filed as #4266): it never declares a
     * `Dungeon $dungeon` parameter even though `{dungeon}` is part of its route, and `mixed
     * $dungeonroute` isn't class-typed so Laravel doesn't model-bind it either. Laravel's
     * ResolvesRouteDependencies splices class-typed dependencies (the request, services) into their
     * declared positions but leaves the raw route values - [dungeon, dungeonroute, title, floorIndex]
     * - untouched at their original indices; PHP then fills the method's two trailing untyped
     * parameters ($dungeonroute, $floorIndex) from the first two of those four raw values. The net
     * effect: `$dungeonroute` inside the method actually receives the URL's {dungeon} slug (a
     * string!), and `$floorIndex` actually receives the URL's {dungeonroute} public_key - the real
     * {title} and {floorIndex} segments are silently dropped. Since a public_key is always
     * non-numeric, the method always takes the "treat floorIndex as a public_key" branch, looks up
     * the SAME dungeonroute the URL already named (self-correcting back to the right model), then
     * forces floorIndex to '1' - so the embed page always renders floor 1 regardless of what
     * {floorIndex} was actually requested. These tests pin that real (buggy) behavior; do not
     * "fix" it here as part of #4256's redirect-logic consolidation.
     */
    #[Test]
    public function embed_alwaysRendersFloorOneRegardlessOfRequestedFloorIndex(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $route = $this->createRoute($owner);
        /** @var Floor $floorOne */
        $floorOne = $route->dungeon->floors()->where('facade', 0)->where('index', 1)->firstOrFail();
        /** @var Floor|null $otherFloor */
        $otherFloor = $route->dungeon->floors()->where('facade', 0)->where('index', '!=', 1)->first();

        try {
            // Act - request an explicit, existing, non-1 floor index
            $response = $this->get(route('dungeonroute.embed.floor', [
                'dungeon'      => $route->dungeon,
                'dungeonroute' => $route,
                'title'        => $route->getTitleSlug(),
                'floorIndex'   => $otherFloor !== null ? $otherFloor->index : $floorOne->index,
            ]));

            // Assert - floor 1 is rendered regardless (see class docblock above this test)
            $response->assertOk();
            $response->assertViewHas('floor', static fn(Floor $viewFloor) => $viewFloor->id === $floorOne->id);
            $response->assertViewHas('dungeonroute', static fn(DungeonRoute $viewRoute) => $viewRoute->id === $route->id);
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function embed_givenNonExistentPublicKeyInDungeonrouteUrlSegment_returnsNotFound(): void
    {
        // Act - per the argument-shift bug documented above, it's the {dungeonroute} URL segment
        // (not {floorIndex}) that actually reaches the public_key lookup
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
        [$dungeon] = $this->findDungeon(facadeEnabled: false, minActiveFloors: 1, requireDefaultFloor: true);

        return DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
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
        [$dungeon] = $this->findDungeon(facadeEnabled: true, minActiveFloors: 1, requireDefaultFloor: true);

        return DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'author_id'          => $owner->id,
            'expires_at'         => null,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ]);
    }
}
