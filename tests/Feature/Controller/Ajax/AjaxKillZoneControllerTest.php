<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\KillZone\KillZone;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Controller\DungeonRouteTestBase;

#[Group('Controller')]
#[Group('KillZone')]
final class AjaxKillZoneControllerTest extends DungeonRouteTestBase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        config(['broadcasting.default' => 'null']);

        // Replace with a non-facade dungeon route so KillZonePathService doesn't fail
        // on facade floor-switch markers when calculateForRoute is called.
        $this->dungeonRoute->delete();
        $this->dungeonRoute = $this->createNonFacadeDungeonRoute();
    }

    #[Test]
    public function storeAll_givenExistingKillZoneId_shouldUpdateKillZone(): void
    {
        // Arrange
        $killZone = KillZone::factory()->create([
            'dungeon_route_id' => $this->dungeonRoute->id,
            'floor_id'         => null,
            'lat'              => null,
            'lng'              => null,
            'color'            => '#000000',
            'index'            => 1,
        ]);

        try {
            // Act
            $response = $this->put(sprintf('/ajax/%s/killzone/mass', $this->dungeonRoute->public_key), [
                'killzones' => [
                    [
                        'id'    => $killZone->id,
                        'color' => '#ff0000',
                        'index' => 1,
                    ],
                ],
            ]);

            // Assert
            $response->assertOk();
            $this->assertDatabaseHas('kill_zones', ['id' => $killZone->id, 'color' => '#ff0000']);
        } finally {
            $killZone->delete();
        }
    }

    #[Test]
    public function storeAll_givenNewKillZone_shouldCreateKillZone(): void
    {
        // Act
        $response = $this->put(sprintf('/ajax/%s/killzone/mass', $this->dungeonRoute->public_key), [
            'killzones' => [
                [
                    'color' => '#00ff00',
                    'index' => 1,
                ],
            ],
        ]);

        // Assert
        $response->assertOk();
        $this->assertEquals(1, $this->dungeonRoute->killZones()->count());
    }
}
