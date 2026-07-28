<?php

namespace Tests\Feature\App\Service\DungeonRoute;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteEnemyRaidMarker;
use App\Models\Enemy;
use App\Models\Mapping\MappingVersion;
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
        [$dungeon, $existingMV, $enemy] = $this->getRetailDungeonWithUniquelyIdentifiedEnemy();
        // Cloning a new mapping version also clones every enemy (same npc_id/mdt_id, new ids)
        $newMV = $this->createNewerMappingVersion($dungeon, $existingMV);

        $route      = DungeonRoute::factory()->create(['dungeon_id' => $dungeon->id, 'mapping_version_id' => $existingMV->id]);
        $raidMarker = $this->createRaidMarker($route, $enemy);

        try {
            // Look up the clone by the same coalesced key updateEnemyIdsByMappingVersion()
            // resolves markers by, not raw npc_id: getRetailDungeonWithUniquelyIdentifiedEnemy()
            // only guarantees uniqueness on (COALESCE(mdt_npc_id, npc_id), mdt_id), so a sibling
            // enemy can share the raw (npc_id, mdt_id) pair while having a different coalesced key.
            /** @var Enemy $clonedEnemy */
            $clonedEnemy = Enemy::where('mapping_version_id', $newMV->id)
                ->whereRaw('COALESCE(mdt_npc_id, npc_id) = ?', [$enemy->getMdtNpcId()])
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
    public function upgradeMappingVersion_givenNpcWithNullMdtIdStillExistsInNewMappingVersion_preservesRaidMarker(): void
    {
        // Arrange - enemies placed outside of an MDT import legitimately have a null mdt_id;
        // the resolution join must be null-safe (`<=>`), not plain `=`, or it can never re-match
        [$dungeon, $existingMV, $enemy] = $this->getRetailDungeonWithNullMdtIdEnemy();
        $newMV                          = $this->createNewerMappingVersion($dungeon, $existingMV);

        $route      = DungeonRoute::factory()->create(['dungeon_id' => $dungeon->id, 'mapping_version_id' => $existingMV->id]);
        $raidMarker = $this->createRaidMarker($route, $enemy);

        try {
            // Same coalesced-key matching as above - raw npc_id alone is not guaranteed to
            // identify the correct clone.
            /** @var Enemy $clonedEnemy */
            $clonedEnemy = Enemy::where('mapping_version_id', $newMV->id)
                ->whereRaw('COALESCE(mdt_npc_id, npc_id) = ?', [$enemy->getMdtNpcId()])
                ->whereNull('mdt_id')
                ->firstOrFail();

            // Act
            app(DungeonRouteServiceInterface::class)->upgradeMappingVersion($route);

            // Assert
            $fresh = $raidMarker->fresh();
            $this->assertNotNull($fresh);
            $this->assertEquals($clonedEnemy->id, $fresh->enemy_id);
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
        [$dungeon, $existingMV, $enemy] = $this->getRetailDungeonWithEnemy();
        $newMV                          = $this->createNewerMappingVersion($dungeon, $existingMV);

        $route      = DungeonRoute::factory()->create(['dungeon_id' => $dungeon->id, 'mapping_version_id' => $existingMV->id]);
        $raidMarker = $this->createRaidMarker($route, $enemy);

        try {
            // Remove the cloned counterpart in the new mapping version, simulating an NPC that
            // was removed from the map in a re-authored version (see the docblock above for why
            // the coalesced key, not raw npc_id, is what must match here).
            Enemy::where('mapping_version_id', $newMV->id)
                ->whereRaw('COALESCE(mdt_npc_id, npc_id) = ?', [$enemy->getMdtNpcId()])
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

    private function createRaidMarker(DungeonRoute $route, Enemy $enemy): DungeonRouteEnemyRaidMarker
    {
        return DungeonRouteEnemyRaidMarker::create([
            'dungeon_route_id' => $route->id,
            'raid_marker_id'   => RaidMarker::ALL['skull'],
            'npc_id'           => $enemy->getMdtNpcId(),
            'mdt_id'           => $enemy->mdt_id,
            'enemy_id'         => $enemy->id,
        ]);
    }

    /**
     * A retail dungeon that actually has at least one enemy on its current mapping version.
     *
     * Picking a random dungeon and *then* querying for an enemy was flaky: 4 of the 71 retail
     * dungeons (The Arcway, Cathedral of Eternal Night, Maw of Souls, Altar of Fangs) have no
     * enemies at all on their current mapping version, so roughly one run in twenty drew one of
     * them, got a null enemy and died with a TypeError in createRaidMarker(). Search across
     * dungeons instead, mirroring getRetailDungeonWithUniquelyIdentifiedEnemy() and
     * getRetailDungeonWithNullMdtIdEnemy() below.
     *
     * No uniqueness constraint is needed here (unlike the two helpers below): this test deletes
     * every enemy sharing the picked enemy's (COALESCE(mdt_npc_id, npc_id), mdt_id) key - the same
     * key updateEnemyIdsByMappingVersion() resolves markers by - in the new mapping version, so an
     * ambiguous pick is deleted just as completely as a unique one.
     *
     * @return array{0: Dungeon, 1: MappingVersion, 2: Enemy}
     */
    private function getRetailDungeonWithEnemy(): array
    {
        $dungeons = Dungeon::whereNotNull('challenge_mode_id')->get()->shuffle();

        foreach ($dungeons as $dungeon) {
            /** @var Dungeon $dungeon */
            $mappingVersion = $dungeon->getCurrentMappingVersion();
            if ($mappingVersion === null) {
                continue;
            }

            $enemy = Enemy::where('mapping_version_id', $mappingVersion->id)->inRandomOrder()->first();

            if ($enemy !== null) {
                return [$dungeon, $mappingVersion, $enemy];
            }
        }

        self::markTestSkipped('Unable to find a retail dungeon with an enemy on its current mapping version');
    }

    /**
     * A retail dungeon that actually has an enemy uniquely identified by its
     * (mdt_npc_id/npc_id, mdt_id) pair on its current mapping version.
     *
     * npc_id/mdt_id is not guaranteed unique within a mapping version - a handful of NPCs are
     * placed twice under the same MDT clone index. Excluding those keeps this test aligned with
     * what production actually resolves (see DungeonRouteEnemyRaidMarkerRepository) instead of
     * comparing against an arbitrary sibling.
     *
     * Picking a random dungeon and *then* filtering was flaky: 4 of the 71 retail dungeons (The
     * Arcway, Cathedral of Eternal Night, Maw of Souls, Altar of Fangs) have no uniquely-identified
     * enemy at all, so roughly one run in twenty drew one of them, got a null enemy and died with a
     * TypeError in createRaidMarker(). Search across dungeons instead, mirroring
     * getRetailDungeonWithNullMdtIdEnemy() below.
     *
     * @return array{0: Dungeon, 1: MappingVersion, 2: Enemy}
     */
    private function getRetailDungeonWithUniquelyIdentifiedEnemy(): array
    {
        $dungeons = Dungeon::whereNotNull('challenge_mode_id')->get()->shuffle();

        foreach ($dungeons as $dungeon) {
            /** @var Dungeon $dungeon */
            $mappingVersion = $dungeon->getCurrentMappingVersion();
            if ($mappingVersion === null) {
                continue;
            }

            $enemy = Enemy::where('mapping_version_id', $mappingVersion->id)
                ->whereRaw('(
                    SELECT COUNT(*) FROM enemies e2
                    WHERE e2.mapping_version_id = enemies.mapping_version_id
                      AND COALESCE(e2.mdt_npc_id, e2.npc_id) = COALESCE(enemies.mdt_npc_id, enemies.npc_id)
                      AND e2.mdt_id <=> enemies.mdt_id
                ) = 1')
                ->inRandomOrder()
                ->first();

            if ($enemy !== null) {
                return [$dungeon, $mappingVersion, $enemy];
            }
        }

        self::markTestSkipped('Unable to find a retail dungeon with a uniquely identified enemy on its current mapping version');
    }

    /**
     * @return array{0: Dungeon, 1: MappingVersion, 2: Enemy}
     */
    private function getRetailDungeonWithNullMdtIdEnemy(): array
    {
        $dungeons = Dungeon::whereNotNull('challenge_mode_id')->get()->shuffle();

        foreach ($dungeons as $dungeon) {
            /** @var Dungeon $dungeon */
            $mappingVersion = $dungeon->getCurrentMappingVersion();
            if ($mappingVersion === null) {
                continue;
            }

            // Same uniqueness constraint as getRetailDungeonWithUniquelyIdentifiedEnemy(): only
            // 152 of the 1549 null-mdt_id enemies are uniquely identified by their
            // (mdt_npc_id/npc_id, mdt_id) pair, and an ambiguous one cannot be re-resolved after
            // the upgrade, so the marker is dropped and the assertion below fails. The test is
            // about null-safe matching (`<=>` rather than `=`), which an unambiguous enemy
            // exercises just as well.
            $enemy = Enemy::where('mapping_version_id', $mappingVersion->id)
                ->whereNull('mdt_id')
                ->whereRaw('(
                    SELECT COUNT(*) FROM enemies e2
                    WHERE e2.mapping_version_id = enemies.mapping_version_id
                      AND COALESCE(e2.mdt_npc_id, e2.npc_id) = COALESCE(enemies.mdt_npc_id, enemies.npc_id)
                      AND e2.mdt_id <=> enemies.mdt_id
                ) = 1')
                ->inRandomOrder()
                ->first();

            if ($enemy !== null) {
                return [$dungeon, $mappingVersion, $enemy];
            }
        }

        self::markTestSkipped('Unable to find a retail dungeon with a null-mdt_id enemy on its current mapping version');
    }
}
