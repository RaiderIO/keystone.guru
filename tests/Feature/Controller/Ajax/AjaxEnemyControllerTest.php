<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\DungeonRoute\DungeonRouteEnemyRaidMarker;
use App\Models\Enemy;
use App\Models\RaidMarker;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Controller\DungeonRouteTestBase;

#[Group('Controller')]
#[Group('Enemy')]
final class AjaxEnemyControllerTest extends DungeonRouteTestBase
{
    #[Test]
    public function setRaidMarker_givenRaidMarkerKey_persistsNpcIdAndMdtId(): void
    {
        // Arrange
        /** @var Enemy $enemy */
        $enemy = Enemy::where('mapping_version_id', $this->dungeonRoute->mapping_version_id)
            ->orderBy('id')
            ->first();

        try {
            // Act
            $response = $this->post(sprintf('/ajax/%s/raidmarker/%s', $this->dungeonRoute->public_key, $enemy->id), [
                'raid_marker_key' => 'skull',
            ]);

            // Assert
            $response->assertSuccessful();

            /** @var DungeonRouteEnemyRaidMarker|null $raidMarker */
            $raidMarker = DungeonRouteEnemyRaidMarker::where('dungeon_route_id', $this->dungeonRoute->id)->first();

            $this->assertNotNull($raidMarker);
            $this->assertEquals($enemy->id, $raidMarker->enemy_id);
            $this->assertEquals($enemy->getMdtNpcId(), $raidMarker->npc_id);
            $this->assertEquals($enemy->mdt_id, $raidMarker->mdt_id);
            $this->assertSame(RaidMarker::ALL[RaidMarker::RAID_MARKER_SKULL], $raidMarker->raid_marker_id);
            $response->assertJson(['key' => 'skull']);
        } finally {
            DungeonRouteEnemyRaidMarker::where('dungeon_route_id', $this->dungeonRoute->id)->delete();
        }
    }

    #[Test]
    public function setRaidMarker_givenEmptyRaidMarkerKey_deletesExistingRaidMarker(): void
    {
        // Arrange
        /** @var Enemy $enemy */
        $enemy = Enemy::where('mapping_version_id', $this->dungeonRoute->mapping_version_id)
            ->orderBy('id')
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
                'raid_marker_key' => '',
            ]);

            // Assert
            $response->assertSuccessful();
            $this->assertDatabaseMissing('dungeon_route_enemy_raid_markers', ['dungeon_route_id' => $this->dungeonRoute->id]);
        } finally {
            DungeonRouteEnemyRaidMarker::where('dungeon_route_id', $this->dungeonRoute->id)->delete();
        }
    }

    #[Test]
    public function setRaidMarker_givenLegacyRaidMarkerName_persistsRaidMarker(): void
    {
        // Arrange
        /** @var Enemy $enemy */
        $enemy = Enemy::where('mapping_version_id', $this->dungeonRoute->mapping_version_id)
            ->orderBy('id')
            ->first();

        try {
            // Act
            $response = $this->post(sprintf('/ajax/%s/raidmarker/%s', $this->dungeonRoute->public_key, $enemy->id), [
                'raid_marker_name' => 'moon',
            ]);

            // Assert
            $response->assertSuccessful();
            $response->assertJson(['key' => 'moon']);
            $this->assertDatabaseHas('dungeon_route_enemy_raid_markers', [
                'dungeon_route_id' => $this->dungeonRoute->id,
                'enemy_id'         => $enemy->id,
                'raid_marker_id'   => RaidMarker::ALL[RaidMarker::RAID_MARKER_MOON],
            ]);
        } finally {
            DungeonRouteEnemyRaidMarker::where('dungeon_route_id', $this->dungeonRoute->id)->delete();
        }
    }

    #[Test]
    public function setRaidMarker_givenRouteUserMayNotEdit_returnsForbidden(): void
    {
        // Arrange - a sandbox route is editable by anyone, so make it a real route of user 1
        /** @var Enemy $enemy */
        $enemy = Enemy::where('mapping_version_id', $this->dungeonRoute->mapping_version_id)
            ->orderBy('id')
            ->first();
        $nonOwner = User::factory()->create();
        $this->dungeonRoute->update(['expires_at' => null]);

        try {
            $this->actingAs($nonOwner);

            // Act
            $response = $this->post(sprintf('/ajax/%s/raidmarker/%s', $this->dungeonRoute->public_key, $enemy->id), [
                'raid_marker_key' => 'skull',
            ]);

            // Assert
            $response->assertForbidden();
            $this->assertDatabaseMissing('dungeon_route_enemy_raid_markers', ['dungeon_route_id' => $this->dungeonRoute->id]);
        } finally {
            DungeonRouteEnemyRaidMarker::where('dungeon_route_id', $this->dungeonRoute->id)->delete();
            $nonOwner->delete();
        }
    }

    #[Test]
    public function setRaidMarker_givenUnknownRaidMarkerKey_returnsNotFound(): void
    {
        // Arrange
        /** @var Enemy $enemy */
        $enemy = Enemy::where('mapping_version_id', $this->dungeonRoute->mapping_version_id)
            ->orderBy('id')
            ->first();

        try {
            // Act
            $response = $this->post(sprintf('/ajax/%s/raidmarker/%s', $this->dungeonRoute->public_key, $enemy->id), [
                'raid_marker_key' => 'not_a_raid_marker',
            ]);

            // Assert
            $response->assertNotFound();
            $this->assertDatabaseMissing('dungeon_route_enemy_raid_markers', ['dungeon_route_id' => $this->dungeonRoute->id]);
        } finally {
            DungeonRouteEnemyRaidMarker::where('dungeon_route_id', $this->dungeonRoute->id)->delete();
        }
    }
}
