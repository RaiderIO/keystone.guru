<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Models\KillZone\KillZone;
use App\Models\PublishedState;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Teapot\StatusCode;
use Tests\Feature\Controller\DungeonRouteTestBase;

#[Group('Controller')]
#[Group('KillZone')]
final class AjaxKillZonePathsControllerTest extends DungeonRouteTestBase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        config(['broadcasting.default' => 'null']);

        $this->dungeonRoute->delete();
        $this->dungeonRoute = $this->createNonFacadeDungeonRouteWithEnemies();
    }

    #[Test]
    public function paths_givenRouteWithNoKillZones_returnsEmptyPaths(): void
    {
        // Act
        $response = $this->get(sprintf('/ajax/%s/killzone/paths', $this->dungeonRoute->public_key));

        // Assert
        $response->assertOk();
        $response->assertJsonStructure(['killzone_paths']);
        $this->assertEmpty($response->json('killzone_paths'));
    }

    #[Test]
    public function paths_givenRouteWithKillZones_returnsPathsWithPoints(): void
    {
        // Arrange
        /** @var Floor $floor */
        $floor = $this->dungeonRoute->dungeon->floors()
            ->where('facade', false)
            ->inRandomOrder()
            ->first();

        $killZone1 = KillZone::factory()->create([
            'dungeon_route_id' => $this->dungeonRoute->id,
            'floor_id'         => $floor->id,
            'lat'              => -128.0,
            'lng'              => 128.0,
            'index'            => 1,
        ]);
        $killZone2 = KillZone::factory()->create([
            'dungeon_route_id' => $this->dungeonRoute->id,
            'floor_id'         => $floor->id,
            'lat'              => -64.0,
            'lng'              => 192.0,
            'index'            => 2,
        ]);

        try {
            // Act
            $response = $this->get(sprintf('/ajax/%s/killzone/paths', $this->dungeonRoute->public_key));

            // Assert
            $response->assertOk();
            $paths = $response->json('killzone_paths');
            $this->assertNotEmpty($paths);
            $firstSegment = $paths[0];
            $this->assertNotEmpty($firstSegment);
            $firstPoint = $firstSegment[0];
            $this->assertArrayHasKey('lat', $firstPoint);
            $this->assertArrayHasKey('lng', $firstPoint);
            $this->assertArrayHasKey('floor_id', $firstPoint);
        } finally {
            $killZone1->delete();
            $killZone2->delete();
        }
    }

    #[Test]
    public function paths_givenUnpublishedRouteAndUnauthorizedUser_returnsForbidden(): void
    {
        // Arrange
        $nonAdmin         = User::factory()->create();
        $unpublishedRoute = DungeonRoute::factory()->create([
            'author_id'          => 1,
            'published_state_id' => PublishedState::ALL[PublishedState::UNPUBLISHED],
            'expires_at'         => null,
        ]);

        try {
            $this->actingAs($nonAdmin);

            // Act
            $response = $this->get(sprintf('/ajax/%s/killzone/paths', $unpublishedRoute->public_key));

            // Assert
            $response->assertStatus(StatusCode::FORBIDDEN);
        } finally {
            $unpublishedRoute->delete();
            $nonAdmin->delete();
        }
    }
}
