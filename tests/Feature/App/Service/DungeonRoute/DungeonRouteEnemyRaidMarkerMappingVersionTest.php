<?php

namespace Tests\Feature\App\Service\DungeonRoute;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteEnemyRaidMarker;
use App\Models\Enemy;
use App\Models\Faction;
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
        // the resolution join must be null-safe (`<=>`), not plain `=`, or it can never re-match.
        // Null mdt_id is now considered an invalid enemy state and has been backfilled out of the
        // seed data entirely, so none exist to search for anymore (see #3713). Construct both
        // sides of the fixture directly instead - the "existing" enemy and its counterpart on the
        // new mapping version - rather than relying on MappingVersion::boot()'s clone-on-create to
        // carry one over: that clone picks its source purely by global version number with no
        // game_version_id scoping, which is not guaranteed to be $existingMV for a dungeon that
        // has mapping versions on more than one game version (e.g. Timewalking dungeons).
        $dungeon    = $this->getRetailDungeon();
        $existingMV = $dungeon->getCurrentMappingVersion();
        $floorId    = $dungeon->floors()->where('facade', false)->value('id');
        $npcId      = 999999999;

        // $enemy is created directly on $existingMV - the dungeon's real, shared, seeded current
        // mapping version - so its creation and cleanup both live inside the try/finally: leaving
        // it behind on a mid-Arrange failure would poison seed data with the exact invalid state
        // #3713 is about. Cleanup below is query-based rather than object-handle-based so it's
        // self-healing even if a prior run crashed before $enemy was assigned.
        try {
            $enemy = $this->createNullMdtIdEnemy($existingMV->id, $floorId, $npcId);
            $newMV = $this->createNewerMappingVersion($dungeon, $existingMV);
            // When boot() does happen to pick $existingMV as its clone source, $enemy was already
            // carried over automatically - clear that opportunistic copy so the explicit
            // counterpart created below is the only match, regardless of which mapping version
            // got cloned.
            Enemy::where('mapping_version_id', $newMV->id)->where('npc_id', $npcId)->delete();
            $clonedEnemy = $this->createNullMdtIdEnemy($newMV->id, $floorId, $npcId);

            $route      = DungeonRoute::factory()->create(['dungeon_id' => $dungeon->id, 'mapping_version_id' => $existingMV->id]);
            $raidMarker = $this->createRaidMarker($route, $enemy);

            // Act
            app(DungeonRouteServiceInterface::class)->upgradeMappingVersion($route);

            // Assert
            $fresh = $raidMarker->fresh();
            $this->assertNotNull($fresh);
            $this->assertEquals($clonedEnemy->id, $fresh->enemy_id);
            $this->assertNull($fresh->mdt_id);
        } finally {
            DungeonRouteEnemyRaidMarker::where('npc_id', $npcId)->delete();
            if (isset($route)) {
                $route->delete();
            }
            if (isset($newMV)) {
                $newMV->delete();
            }
            Enemy::where('mapping_version_id', $existingMV->id)->where('npc_id', $npcId)->delete();
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

    private function createNullMdtIdEnemy(int $mappingVersionId, int $floorId, int $npcId): Enemy
    {
        return Enemy::create([
            'mapping_version_id' => $mappingVersionId,
            'floor_id'           => $floorId,
            'npc_id'             => $npcId,
            'mdt_id'             => null,
            'faction'            => Faction::ALL[Faction::FACTION_UNSPECIFIED],
            'lat'                => -100.0,
            'lng'                => 100.0,
        ]);
    }

    /**
     * A retail dungeon that actually has at least one enemy on its current mapping version.
     *
     * Picking a random dungeon and *then* querying for an enemy was flaky: 4 of the 71 retail
     * dungeons (The Arcway, Cathedral of Eternal Night, Maw of Souls, Altar of Fangs) have no
     * enemies at all on their current mapping version, so roughly one run in twenty drew one of
     * them, got a null enemy and died with a TypeError in createRaidMarker(). Search across
     * dungeons instead, mirroring getRetailDungeonWithUniquelyIdentifiedEnemy() below.
     *
     * No uniqueness constraint is needed here (unlike the helper below): this test deletes
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

        throw new \RuntimeException('Unable to find a retail dungeon with an enemy on its current mapping version');
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
     * TypeError in createRaidMarker(). Search across dungeons instead.
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

        throw new \RuntimeException('Unable to find a retail dungeon with a uniquely identified enemy on its current mapping version');
    }
}
