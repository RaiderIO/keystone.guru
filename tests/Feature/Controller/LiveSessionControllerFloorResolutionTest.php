<?php

namespace Tests\Feature\Controller;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Models\Laratrust\Role;
use App\Models\LiveSession;
use App\Models\PublishedState;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('LiveSession')]
final class LiveSessionControllerFloorResolutionTest extends PublicTestCase
{
    use ProvidesDungeon;

    #[Test]
    public function viewFloor_givenExistingFloorIndex_returnsOk(): void
    {
        // Arrange
        [$owner, $route, $liveSession] = $this->createLiveSession();
        /** @var Floor $floor */
        $floor = Floor::where('dungeon_id', $route->dungeon_id)->defaultOrFacade($route->mappingVersion)->first();

        try {
            $this->be($owner);

            // Act
            $response = $this->get(route('dungeonroute.livesession.viewfloor', [
                'dungeon'      => $route->dungeon,
                'dungeonroute' => $route,
                'title'        => $route->getTitleSlug(),
                'liveSession'  => $liveSession,
                'floorIndex'   => $floor->index,
            ]));

            // Assert
            $response->assertOk();
        } finally {
            $this->cleanupLiveSession($liveSession, $route, $owner);
        }
    }

    #[Test]
    public function viewFloor_givenNonExistentFloorIndex_rendersDefaultFloorWithoutRedirecting(): void
    {
        // Arrange
        [$owner, $route, $liveSession] = $this->createLiveSession();
        /** @var Floor $defaultFloor */
        $defaultFloor = Floor::where('dungeon_id', $route->dungeon_id)->defaultOrFacade($route->mappingVersion)->first();

        try {
            $this->be($owner);

            // Act
            $response = $this->get(route('dungeonroute.livesession.viewfloor', [
                'dungeon'      => $route->dungeon,
                'dungeonroute' => $route,
                'title'        => $route->getTitleSlug(),
                'liveSession'  => $liveSession,
                'floorIndex'   => 999999,
            ]));

            // Assert - unlike DungeonExploreController/DungeonHeatmapController/AdminToolsCombatLogController,
            // this does NOT redirect to a canonical URL. Floor::indexOrFacade()'s query always falls
            // back to the floor flagged `default` via an `orWhere('default', 1)`, so a mismatched
            // numeric index still resolves to a non-null Floor (the default one) and the page renders
            // directly under the requested (non-canonical) URL.
            $response->assertOk();
            $response->assertViewHas('floor', static fn(Floor $floor) => $floor->id === $defaultFloor->id);
        } finally {
            $this->cleanupLiveSession($liveSession, $route, $owner);
        }
    }

    /**
     * @return array{0: User, 1: DungeonRoute, 2: LiveSession}
     */
    private function createLiveSession(): array
    {
        $owner = User::factory()->create();
        $owner->addRole(Role::firstWhere('name', Role::ROLE_USER));

        [$dungeon] = $this->findDungeon(facadeEnabled: false, minActiveFloors: 1, requireDefaultFloor: true);

        $route = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'author_id'          => $owner->id,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
            'expires_at'         => null,
        ]);

        $liveSession = LiveSession::create([
            'dungeon_route_id' => $route->id,
            'user_id'          => $owner->id,
            'public_key'       => LiveSession::generateRandomPublicKey(),
        ]);

        return [$owner, $route, $liveSession];
    }

    private function cleanupLiveSession(LiveSession $liveSession, DungeonRoute $route, User $owner): void
    {
        // Mass delete on purpose: LiveSession's "deleting" hook cascades into overpulled_enemies,
        // a table no migration creates, so $liveSession->delete() throws on a migrated database.
        LiveSession::query()->whereKey($liveSession->id)->delete();
        $route->delete();
        $owner->delete();
    }
}
