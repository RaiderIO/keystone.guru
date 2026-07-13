<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\Enemy;
use App\Models\KillZone\KillZone;
use App\Models\KillZone\KillZoneEnemy;
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
        $this->dungeonRoute = $this->createNonFacadeDungeonRouteWithEnemies();
        // MySQL can recycle IDs after a restart, leaving orphaned kill_zones rows from a previous
        // test run attached to this newly created route ID. Delete them to start from a clean state.
        $this->dungeonRoute->killZones()->delete();
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

    #[Test]
    public function store_givenEnemyIds_shouldSetEnemyIdOnKillZoneEnemies(): void
    {
        // Arrange
        /** @var Enemy $enemy */
        $enemy = Enemy::where('mapping_version_id', $this->dungeonRoute->mapping_version_id)
            ->inRandomOrder()
            ->first();

        try {
            // Act
            $response = $this->post(sprintf('/ajax/%s/killzone', $this->dungeonRoute->public_key), [
                'color'   => '#ff0000',
                'index'   => 1,
                'enemies' => [$enemy->id],
                'spells'  => [],
            ]);

            // Assert
            $response->assertSuccessful();

            /** @var KillZone $killZone */
            $killZone = $this->dungeonRoute->killZones()->first();
            /** @var KillZoneEnemy|null $killZoneEnemy */
            $killZoneEnemy = KillZoneEnemy::where('kill_zone_id', $killZone->id)->first();

            $this->assertNotNull($killZoneEnemy);
            $this->assertEquals($enemy->id, $killZoneEnemy->enemy_id);
        } finally {
            $this->dungeonRoute->killZones()->delete();
        }
    }

    #[Test]
    public function storeAll_givenEnemyIds_shouldSetEnemyIdOnKillZoneEnemies(): void
    {
        // Arrange
        /** @var Enemy $enemy */
        $enemy    = Enemy::where('mapping_version_id', $this->dungeonRoute->mapping_version_id)->inRandomOrder()->first();
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
                        'id'      => $killZone->id,
                        'color'   => '#ff0000',
                        'index'   => 1,
                        'enemies' => [$enemy->id],
                    ],
                ],
            ]);

            // Assert
            $response->assertOk();

            $killZoneEnemy = KillZoneEnemy::where('kill_zone_id', $killZone->id)->first();
            $this->assertNotNull($killZoneEnemy);
            $this->assertEquals($enemy->id, $killZoneEnemy->enemy_id);
        } finally {
            $killZone->delete();
        }
    }
}
