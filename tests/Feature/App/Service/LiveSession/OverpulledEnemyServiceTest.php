<?php

namespace Tests\Feature\App\Service\LiveSession;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\KillZone\KillZone;
use App\Models\KillZone\KillZoneEnemy;
use App\Models\LiveSession\LiveSession;
use App\Models\LiveSession\LiveSessionKilledEnemy;
use App\Models\LiveSession\LiveSessionOverpulledEnemy;
use App\Service\LiveSession\OverpulledEnemyServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Uses Freehold (challenge_mode_id = 245) because it has skippable enemies in
 * its current mapping version.
 */
#[Group('LiveSession')]
#[Group('OverpulledEnemyService')]
final class OverpulledEnemyServiceTest extends PublicTestCase
{
    private const int FREEHOLD_CHALLENGE_MODE_ID = 245;

    // npc_id=127106, mdt_id=1, enemy_forces=8 (non-skippable) — used as the overpulled enemy
    private const int OVERPULL_NPC_ID = 127106;

    private const int OVERPULL_MDT_ID = 1;

    // npc_id=126918, mdt_id=14, enemy_pack_id=null, enemy_forces=4 (skippable, no pack) — becomes obsolete
    private const int SKIPPABLE_NPC_ID = 126918;

    private const int SKIPPABLE_MDT_ID = 14;

    private const int SKIPPABLE_ENEMY_ID = 27963;

    // Two downstream skippable enemies (5 forces each, no pack) sharing npc_id=129526 — used to prove that a
    // killed obsolete enemy is replaced by a later skippable one.
    private const int SKIPPABLE_A_ENEMY_ID = 28142;

    private const int SKIPPABLE_A_NPC_ID = 129526;

    private const int SKIPPABLE_A_MDT_ID = 2;

    private const int SKIPPABLE_B_ENEMY_ID = 28143;

    private const int SKIPPABLE_B_NPC_ID = 129526;

    private const int SKIPPABLE_B_MDT_ID = 3;

    // -------------------------------------------------------------------------
    // getRouteCorrection
    // -------------------------------------------------------------------------

    #[Test]
    public function getRouteCorrection_givenLiveSessionIdDifferentFromDungeonRouteId_returnsNonEmptyObsoleteSet(): void
    {
        // Arrange
        $dungeon        = Dungeon::where('challenge_mode_id', self::FREEHOLD_CHALLENGE_MODE_ID)->firstOrFail();
        $mappingVersion = $dungeon->getCurrentMappingVersion();

        if ($mappingVersion === null) {
            $this->markTestSkipped('No current mapping version for Freehold');
        }

        /** @var DungeonRoute $dungeonRoute */
        $dungeonRoute = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
        ]);

        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create([
            'dungeon_route_id' => $dungeonRoute->id,
        ]);

        // The original SQL bug was: dungeon_routes.id = live_sessions.id instead of = dungeon_route_id.
        // When the two IDs are equal the bug is accidentally hidden, so we assert they are different.
        $this->assertNotSame(
            $liveSession->id,
            $dungeonRoute->id,
            'Test requires live_session.id != dungeon_route_id to exercise the join bug; run on a non-fresh database.',
        );

        // Kill zone 1: where the overpull is attributed
        $killZone1 = KillZone::factory()->create([
            'dungeon_route_id' => $dungeonRoute->id,
            'index'            => 1,
        ]);

        // Kill zone 2: downstream skippable enemy — becomes obsolete to compensate the overpull
        $killZone2 = KillZone::factory()->create([
            'dungeon_route_id' => $dungeonRoute->id,
            'index'            => 2,
        ]);

        try {
            KillZoneEnemy::factory()->create([
                'kill_zone_id' => $killZone2->id,
                'npc_id'       => self::SKIPPABLE_NPC_ID,
                'mdt_id'       => self::SKIPPABLE_MDT_ID,
                'enemy_id'     => self::SKIPPABLE_ENEMY_ID,
            ]);

            // Overpulled enemy (8 forces) attributed to kill zone 1
            LiveSessionOverpulledEnemy::query()->create([
                'live_session_id' => $liveSession->id,
                'kill_zone_id'    => $killZone1->id,
                'npc_id'          => self::OVERPULL_NPC_ID,
                'mdt_id'          => self::OVERPULL_MDT_ID,
            ]);

            $liveSession->load('dungeonRoute.mappingVersion');

            /** @var OverpulledEnemyServiceInterface $service */
            $service = app()->make(OverpulledEnemyServiceInterface::class);

            // Act
            $correction = $service->getRouteCorrection($liveSession);

            // Assert: the downstream skippable enemy (4 forces) is marked obsolete because
            // the overpull (8 forces) exceeds the available correction budget (4 forces needed)
            $this->assertNotEmpty($correction->getObsoleteEnemies()->toArray());
            $this->assertContains(self::SKIPPABLE_ENEMY_ID, $correction->getObsoleteEnemies()->toArray());
        } finally {
            LiveSessionOverpulledEnemy::query()->where('live_session_id', $liveSession->id)->delete();
            KillZoneEnemy::query()->where('kill_zone_id', $killZone2->id)->delete();
            $killZone2->delete();
            $killZone1->delete();
            $liveSession->delete();
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function getRouteCorrection_givenObsoleteEnemyAlsoKilled_marksDifferentEnemyObsolete(): void
    {
        // Arrange
        $dungeon        = Dungeon::where('challenge_mode_id', self::FREEHOLD_CHALLENGE_MODE_ID)->firstOrFail();
        $mappingVersion = $dungeon->getCurrentMappingVersion();

        if ($mappingVersion === null) {
            $this->markTestSkipped('No current mapping version for Freehold');
        }

        /** @var DungeonRoute $dungeonRoute */
        $dungeonRoute = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
        ]);

        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create(['dungeon_route_id' => $dungeonRoute->id]);

        // KZ1: where the overpull (8 forces) is attributed. KZ2/KZ3: one skippable enemy (5 forces) each.
        $killZone1 = KillZone::factory()->create(['dungeon_route_id' => $dungeonRoute->id, 'index' => 1]);
        $killZone2 = KillZone::factory()->create(['dungeon_route_id' => $dungeonRoute->id, 'index' => 2]);
        $killZone3 = KillZone::factory()->create(['dungeon_route_id' => $dungeonRoute->id, 'index' => 3]);

        try {
            KillZoneEnemy::factory()->create([
                'kill_zone_id' => $killZone2->id,
                'npc_id'       => self::SKIPPABLE_A_NPC_ID,
                'mdt_id'       => self::SKIPPABLE_A_MDT_ID,
                'enemy_id'     => self::SKIPPABLE_A_ENEMY_ID,
            ]);
            KillZoneEnemy::factory()->create([
                'kill_zone_id' => $killZone3->id,
                'npc_id'       => self::SKIPPABLE_B_NPC_ID,
                'mdt_id'       => self::SKIPPABLE_B_MDT_ID,
                'enemy_id'     => self::SKIPPABLE_B_ENEMY_ID,
            ]);

            LiveSessionOverpulledEnemy::query()->create([
                'live_session_id' => $liveSession->id,
                'kill_zone_id'    => $killZone1->id,
                'npc_id'          => self::OVERPULL_NPC_ID,
                'mdt_id'          => self::OVERPULL_MDT_ID,
            ]);

            // The earlier skippable enemy (A) was actually killed, so it can no longer be skipped
            LiveSessionKilledEnemy::query()->create([
                'live_session_id' => $liveSession->id,
                'npc_id'          => self::SKIPPABLE_A_NPC_ID,
                'mdt_id'          => self::SKIPPABLE_A_MDT_ID,
            ]);

            $liveSession->load('dungeonRoute.mappingVersion');

            /** @var OverpulledEnemyServiceInterface $service */
            $service = app()->make(OverpulledEnemyServiceInterface::class);

            // Act
            $correction      = $service->getRouteCorrection($liveSession);
            $obsoleteEnemies = $correction->getObsoleteEnemies()->toArray();

            // Assert: the killed enemy (A) is NOT marked obsolete; the later skippable enemy (B) is instead
            $this->assertNotContains(self::SKIPPABLE_A_ENEMY_ID, $obsoleteEnemies);
            $this->assertContains(self::SKIPPABLE_B_ENEMY_ID, $obsoleteEnemies);
        } finally {
            LiveSessionKilledEnemy::query()->where('live_session_id', $liveSession->id)->delete();
            LiveSessionOverpulledEnemy::query()->where('live_session_id', $liveSession->id)->delete();
            KillZoneEnemy::query()->whereIn('kill_zone_id', [$killZone2->id, $killZone3->id])->delete();
            $killZone3->delete();
            $killZone2->delete();
            $killZone1->delete();
            $liveSession->delete();
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function getRouteCorrection_givenAllSkippableEnemiesKilled_returnsNoObsolete(): void
    {
        // Arrange
        $dungeon        = Dungeon::where('challenge_mode_id', self::FREEHOLD_CHALLENGE_MODE_ID)->firstOrFail();
        $mappingVersion = $dungeon->getCurrentMappingVersion();

        if ($mappingVersion === null) {
            $this->markTestSkipped('No current mapping version for Freehold');
        }

        /** @var DungeonRoute $dungeonRoute */
        $dungeonRoute = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
        ]);

        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create(['dungeon_route_id' => $dungeonRoute->id]);

        $killZone1 = KillZone::factory()->create(['dungeon_route_id' => $dungeonRoute->id, 'index' => 1]);
        $killZone2 = KillZone::factory()->create(['dungeon_route_id' => $dungeonRoute->id, 'index' => 2]);
        $killZone3 = KillZone::factory()->create(['dungeon_route_id' => $dungeonRoute->id, 'index' => 3]);

        try {
            KillZoneEnemy::factory()->create([
                'kill_zone_id' => $killZone2->id,
                'npc_id'       => self::SKIPPABLE_A_NPC_ID,
                'mdt_id'       => self::SKIPPABLE_A_MDT_ID,
                'enemy_id'     => self::SKIPPABLE_A_ENEMY_ID,
            ]);
            KillZoneEnemy::factory()->create([
                'kill_zone_id' => $killZone3->id,
                'npc_id'       => self::SKIPPABLE_B_NPC_ID,
                'mdt_id'       => self::SKIPPABLE_B_MDT_ID,
                'enemy_id'     => self::SKIPPABLE_B_ENEMY_ID,
            ]);

            LiveSessionOverpulledEnemy::query()->create([
                'live_session_id' => $liveSession->id,
                'kill_zone_id'    => $killZone1->id,
                'npc_id'          => self::OVERPULL_NPC_ID,
                'mdt_id'          => self::OVERPULL_MDT_ID,
            ]);

            // Both downstream skippable enemies were killed — no candidates remain to correct the overpull
            foreach ([[self::SKIPPABLE_A_NPC_ID, self::SKIPPABLE_A_MDT_ID], [self::SKIPPABLE_B_NPC_ID, self::SKIPPABLE_B_MDT_ID]] as [$npcId, $mdtId]) {
                LiveSessionKilledEnemy::query()->create([
                    'live_session_id' => $liveSession->id,
                    'npc_id'          => $npcId,
                    'mdt_id'          => $mdtId,
                ]);
            }

            $liveSession->load('dungeonRoute.mappingVersion');

            /** @var OverpulledEnemyServiceInterface $service */
            $service = app()->make(OverpulledEnemyServiceInterface::class);

            // Act
            $correction = $service->getRouteCorrection($liveSession);

            // Assert: nothing left to mark obsolete
            $this->assertEmpty($correction->getObsoleteEnemies()->toArray());
        } finally {
            LiveSessionKilledEnemy::query()->where('live_session_id', $liveSession->id)->delete();
            LiveSessionOverpulledEnemy::query()->where('live_session_id', $liveSession->id)->delete();
            KillZoneEnemy::query()->whereIn('kill_zone_id', [$killZone2->id, $killZone3->id])->delete();
            $killZone3->delete();
            $killZone2->delete();
            $killZone1->delete();
            $liveSession->delete();
            $dungeonRoute->delete();
        }
    }
}
