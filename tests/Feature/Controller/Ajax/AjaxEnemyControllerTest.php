<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\DungeonRoute\DungeonRouteEnemyRaidMarker;
use App\Models\Enemy;
use App\Models\RaidMarker;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Controller\DungeonRouteTestBase;

#[Group('Controller')]
#[Group('Enemy')]
final class AjaxEnemyControllerTest extends DungeonRouteTestBase
{
    #[Test]
    public function setRaidMarker_givenRaidMarkerName_persistsNpcIdAndMdtId(): void
    {
        // Arrange
        /** @var Enemy $enemy */
        $enemy = Enemy::where('mapping_version_id', $this->dungeonRoute->mapping_version_id)
            ->inRandomOrder()
            ->first();

        try {
            // Act
            $response = $this->post(sprintf('/ajax/%s/raidmarker/%s', $this->dungeonRoute->public_key, $enemy->id), [
                'raid_marker_name' => 'skull',
            ]);

            // Assert
            $response->assertSuccessful();

            /** @var DungeonRouteEnemyRaidMarker|null $raidMarker */
            $raidMarker = DungeonRouteEnemyRaidMarker::where('dungeon_route_id', $this->dungeonRoute->id)->first();

            $this->assertNotNull($raidMarker);
            $this->assertEquals($enemy->id, $raidMarker->enemy_id);
            $this->assertEquals($enemy->getMdtNpcId(), $raidMarker->npc_id);
            $this->assertEquals($enemy->mdt_id, $raidMarker->mdt_id);
        } finally {
            DungeonRouteEnemyRaidMarker::where('dungeon_route_id', $this->dungeonRoute->id)->delete();
        }
    }

    #[Test]
    public function setRaidMarker_givenEmptyRaidMarkerName_deletesExistingRaidMarker(): void
    {
        // Arrange
        /** @var Enemy $enemy */
        $enemy = Enemy::where('mapping_version_id', $this->dungeonRoute->mapping_version_id)
            ->inRandomOrder()
            ->first();

        DungeonRouteEnemyRaidMarker::create([
            'dungeon_route_id' => $this->dungeonRoute->id,
            'raid_marker_id'   => RaidMarker::ALL['skull'],
            'npc_id'           => $enemy->getMdtNpcId(),
            'mdt_id'           => $enemy->mdt_id,
            'enemy_id'         => $enemy->id,
        ]);

        try {
            // Act
            $response = $this->post(sprintf('/ajax/%s/raidmarker/%s', $this->dungeonRoute->public_key, $enemy->id), [
                'raid_marker_name' => '',
            ]);

            // Assert
            $response->assertSuccessful();
            $this->assertDatabaseMissing('dungeon_route_enemy_raid_markers', ['dungeon_route_id' => $this->dungeonRoute->id]);
        } finally {
            DungeonRouteEnemyRaidMarker::where('dungeon_route_id', $this->dungeonRoute->id)->delete();
        }
    }
}
