<?php

namespace Tests\Feature\App\Service\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteEnemyRaidMarker;
use App\Models\Enemy;
use App\Models\RaidMarker;
use App\Service\DungeonRoute\DungeonRouteServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('DungeonRouteService')]
final class DungeonRouteEnemyRaidMarkerMappingVersionTest extends DungeonRouteSaveServiceTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        config(['broadcasting.default' => 'null']);
    }

    #[Test]
    public function upgradeMappingVersion_givenNpcStillExistsInNewMappingVersion_preservesRaidMarker(): void
    {
        // Arrange
        $dungeon    = $this->getRetailDungeon();
        $existingMV = $dungeon->getCurrentMappingVersion();
        // Cloning a new mapping version also clones every enemy (same npc_id/mdt_id, new ids)
        $newMV = $this->createNewerMappingVersion($dungeon, $existingMV);

        /** @var Enemy $enemy */
        $enemy = Enemy::where('mapping_version_id', $existingMV->id)->inRandomOrder()->first();

        $route = DungeonRoute::factory()->create(['dungeon_id' => $dungeon->id, 'mapping_version_id' => $existingMV->id]);

        $raidMarker = DungeonRouteEnemyRaidMarker::create([
            'dungeon_route_id' => $route->id,
            'raid_marker_id'   => RaidMarker::ALL['skull'],
            'npc_id'           => $enemy->mdt_npc_id ?? $enemy->npc_id,
            'mdt_id'           => $enemy->mdt_id,
            'enemy_id'         => $enemy->id,
        ]);

        try {
            /** @var Enemy $clonedEnemy */
            $clonedEnemy = Enemy::where('mapping_version_id', $newMV->id)
                ->where('npc_id', $enemy->npc_id)
                ->where('mdt_id', $enemy->mdt_id)
                ->firstOrFail();

            // Act
            app(DungeonRouteServiceInterface::class)->upgradeMappingVersion($route);

            // Assert
            $fresh = $raidMarker->fresh();
            $this->assertNotNull($fresh);
            $this->assertEquals($clonedEnemy->id, $fresh->enemy_id);
            $this->assertEquals($raidMarker->npc_id, $fresh->npc_id);
            $this->assertEquals($raidMarker->mdt_id, $fresh->mdt_id);
        } finally {
            $raidMarker->delete();
            $route->delete();
            $newMV->delete();
        }
    }

    #[Test]
    public function upgradeMappingVersion_givenNpcNoLongerExistsInNewMappingVersion_deletesRaidMarker(): void
    {
        // Arrange
        $dungeon    = $this->getRetailDungeon();
        $existingMV = $dungeon->getCurrentMappingVersion();
        $newMV      = $this->createNewerMappingVersion($dungeon, $existingMV);

        /** @var Enemy $enemy */
        $enemy = Enemy::where('mapping_version_id', $existingMV->id)->inRandomOrder()->first();

        $route = DungeonRoute::factory()->create(['dungeon_id' => $dungeon->id, 'mapping_version_id' => $existingMV->id]);

        $raidMarker = DungeonRouteEnemyRaidMarker::create([
            'dungeon_route_id' => $route->id,
            'raid_marker_id'   => RaidMarker::ALL['skull'],
            'npc_id'           => $enemy->mdt_npc_id ?? $enemy->npc_id,
            'mdt_id'           => $enemy->mdt_id,
            'enemy_id'         => $enemy->id,
        ]);

        try {
            // Remove the cloned counterpart in the new mapping version, simulating an NPC that
            // was removed from the map in a re-authored version.
            Enemy::where('mapping_version_id', $newMV->id)
                ->where('npc_id', $enemy->npc_id)
                ->where('mdt_id', $enemy->mdt_id)
                ->delete();

            // Act
            app(DungeonRouteServiceInterface::class)->upgradeMappingVersion($route);

            // Assert
            $this->assertNull($raidMarker->fresh());
        } finally {
            DungeonRouteEnemyRaidMarker::where('dungeon_route_id', $route->id)->delete();
            $route->delete();
            $newMV->delete();
        }
    }
}
