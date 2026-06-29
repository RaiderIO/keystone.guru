<?php

namespace Tests\Feature\App\Service\LiveSession;

use App\Events\LiveSession\InCombatEnemiesChangedEvent;
use App\Events\LiveSession\OverpulledEnemy\OverpulledEnemyChangedEvent;
use App\Events\LiveSession\RouteCorrectionEvent;
use App\Events\Models\LiveSession\EnemyKilledEvent;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\KillZone\KillZone;
use App\Models\KillZone\KillZoneEnemy;
use App\Models\LiveSession\LiveSession;
use App\Models\LiveSession\LiveSessionInCombatEnemy;
use App\Models\LiveSession\LiveSessionKilledEnemy;
use App\Models\LiveSession\LiveSessionObsoleteEnemy;
use App\Models\LiveSession\LiveSessionOverpulledEnemy;
use App\Service\LiveSession\LiveSessionOverpullDetectionServiceInterface;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Uses Freehold (challenge_mode_id = 245, mapping_version_id = 186) for consistent test data:
 *  - ON_ROUTE enemy  : id=28138, npc_id=127106, mdt_id=1, enemy_forces=8, enemy_pack_id=5883
 *  - OFF_ROUTE enemy : id=28139, npc_id=127106, mdt_id=2, enemy_pack_id=5880 (never added to a kill zone)
 *  - SKIPPABLE enemy : id=27963, npc_id=126918, mdt_id=14, enemy_pack_id=null, enemy_forces=4, skippable=1
 *  - OVERPULL enemy  : id=28138's sibling npc_id=127106, mdt_id=2 is off-route so it acts as overpull
 */
#[Group('LiveSession')]
#[Group('LiveSessionOverpullDetection')]
final class LiveSessionOverpullDetectionServiceTest extends PublicTestCase
{
    private const int FREEHOLD_CHALLENGE_MODE_ID = 245;

    // Assigned to kill zone 1 — on-route
    private const int ON_ROUTE_ENEMY_ID = 28138;

    // NOT assigned to any kill zone — always off-route
    private const int OFF_ROUTE_ENEMY_ID = 28139;

    // Skippable, no pack (enemy_pack_id=null), enemy_forces=4 — becomes obsolete when overpull > 4
    private const int SKIPPABLE_ENEMY_ID = 27963;

    private const int SKIPPABLE_NPC_ID = 126918;

    private const int SKIPPABLE_MDT_ID = 14;

    // Two downstream skippable enemies (4 forces each, no pack). With an 8-force overpull the budget covers
    // both, so both are obsolete initially; once A is killed it must drop out while B stays obsolete.
    private const int SKIPPABLE_A_ENEMY_ID = 27963;

    private const int SKIPPABLE_A_NPC_ID = 126918;

    private const int SKIPPABLE_A_MDT_ID = 14;

    private const int SKIPPABLE_B_ENEMY_ID = 28176;

    private const int SKIPPABLE_B_NPC_ID = 155434;

    private const int SKIPPABLE_B_MDT_ID = 9;

    // -------------------------------------------------------------------------
    // processResolvedKills — on-route kills
    // -------------------------------------------------------------------------

    #[Test]
    public function processResolvedKills_givenOnRouteKill_persistsKilledRowAndBroadcastsEnemyKilledEvent(): void
    {
        // Arrange
        Event::fake([EnemyKilledEvent::class, OverpulledEnemyChangedEvent::class, RouteCorrectionEvent::class]);

        [$dungeonRoute, $liveSession, $killZone1] = $this->arrangeFreeholdSession();
        $onRouteEnemy                             = Enemy::findOrFail(self::ON_ROUTE_ENEMY_ID);

        try {
            /** @var LiveSessionOverpullDetectionServiceInterface $service */
            $service = app()->make(LiveSessionOverpullDetectionServiceInterface::class);

            // Act
            $service->processResolvedKills($liveSession, collect([$onRouteEnemy]), collect());

            // Assert: persisted as killed
            $this->assertDatabaseHas('live_session_killed_enemies', [
                'live_session_id' => $liveSession->id,
                'npc_id'          => $onRouteEnemy->npc_id,
                'mdt_id'          => $onRouteEnemy->mdt_id,
            ]);

            // Assert: EnemyKilledEvent dispatched (not overpulled)
            Event::assertDispatched(EnemyKilledEvent::class);
            Event::assertNotDispatched(OverpulledEnemyChangedEvent::class);
        } finally {
            $this->cleanupSession($liveSession->id, [$killZone1->id]);
            $liveSession->delete();
            $dungeonRoute->delete();
        }
    }

    // -------------------------------------------------------------------------
    // processResolvedKills — off-route kills
    // -------------------------------------------------------------------------

    #[Test]
    public function processResolvedKills_givenOffRouteKill_persistsOverpulledRowAndBroadcastsOverpulledAndRouteCorrectionEvents(): void
    {
        // Arrange
        Event::fake([EnemyKilledEvent::class, OverpulledEnemyChangedEvent::class, RouteCorrectionEvent::class]);

        [$dungeonRoute, $liveSession, $killZone1] = $this->arrangeFreeholdSession();
        $offRouteEnemy                            = Enemy::findOrFail(self::OFF_ROUTE_ENEMY_ID);

        // Attribution requires at least one kill zone; kill zone 1 acts as the first
        try {
            /** @var LiveSessionOverpullDetectionServiceInterface $service */
            $service = app()->make(LiveSessionOverpullDetectionServiceInterface::class);

            // Act
            $service->processResolvedKills($liveSession, collect([$offRouteEnemy]), collect());

            // Assert: persisted as overpulled, attributed to kill zone 1 (the firstKillZone)
            $this->assertDatabaseHas('live_session_overpulled_enemies', [
                'live_session_id' => $liveSession->id,
                'npc_id'          => $offRouteEnemy->npc_id,
                'mdt_id'          => $offRouteEnemy->mdt_id,
                'kill_zone_id'    => $killZone1->id,
            ]);

            // Assert: overpulled + route-correction events, no killed event
            Event::assertDispatched(OverpulledEnemyChangedEvent::class);
            Event::assertNotDispatched(EnemyKilledEvent::class);
        } finally {
            $this->cleanupSession($liveSession->id, [$killZone1->id]);
            $liveSession->delete();
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function processResolvedKills_givenOffRouteKillAfterOnRouteKill_attributesToCurrentPullKillZone(): void
    {
        // Arrange
        Event::fake([EnemyKilledEvent::class, OverpulledEnemyChangedEvent::class, RouteCorrectionEvent::class]);

        [$dungeonRoute, $liveSession, $killZone1] = $this->arrangeFreeholdSession();

        // Add a second kill zone — the on-route enemy is in kill zone 2
        $killZone2 = KillZone::factory()->create([
            'dungeon_route_id' => $dungeonRoute->id,
            'index'            => 2,
        ]);
        KillZoneEnemy::query()->create([
            'kill_zone_id' => $killZone2->id,
            'npc_id'       => Enemy::find(self::ON_ROUTE_ENEMY_ID)->npc_id,
            'mdt_id'       => Enemy::find(self::ON_ROUTE_ENEMY_ID)->mdt_id,
            'enemy_id'     => self::ON_ROUTE_ENEMY_ID,
        ]);

        $onRouteEnemy  = Enemy::findOrFail(self::ON_ROUTE_ENEMY_ID);
        $offRouteEnemy = Enemy::findOrFail(self::OFF_ROUTE_ENEMY_ID);

        try {
            /** @var LiveSessionOverpullDetectionServiceInterface $service */
            $service = app()->make(LiveSessionOverpullDetectionServiceInterface::class);

            // Act: on-route kill first (sets currentPullKillZone = killZone2), then off-route
            $service->processResolvedKills($liveSession, collect([$onRouteEnemy, $offRouteEnemy]), collect());

            // Assert: overpull is attributed to kill zone 2 (the current pull), not kill zone 1 (first)
            $this->assertDatabaseHas('live_session_overpulled_enemies', [
                'live_session_id' => $liveSession->id,
                'npc_id'          => $offRouteEnemy->npc_id,
                'mdt_id'          => $offRouteEnemy->mdt_id,
                'kill_zone_id'    => $killZone2->id,
            ]);
        } finally {
            $this->cleanupSession($liveSession->id, [$killZone1->id, $killZone2->id]);
            $liveSession->delete();
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function processResolvedKills_givenOffRouteKillBeforeAnyOnRouteKill_attributesToFirstKillZone(): void
    {
        // Arrange
        Event::fake([EnemyKilledEvent::class, OverpulledEnemyChangedEvent::class, RouteCorrectionEvent::class]);

        [$dungeonRoute, $liveSession, $killZone1] = $this->arrangeFreeholdSession();
        $offRouteEnemy                            = Enemy::findOrFail(self::OFF_ROUTE_ENEMY_ID);

        try {
            /** @var LiveSessionOverpullDetectionServiceInterface $service */
            $service = app()->make(LiveSessionOverpullDetectionServiceInterface::class);

            // Act: off-route before any on-route kill → currentPullKillZone is null → uses firstKillZone
            $service->processResolvedKills($liveSession, collect([$offRouteEnemy]), collect());

            // Assert: attributed to kill zone 1 (the only / first kill zone)
            $this->assertDatabaseHas('live_session_overpulled_enemies', [
                'live_session_id' => $liveSession->id,
                'kill_zone_id'    => $killZone1->id,
            ]);
        } finally {
            $this->cleanupSession($liveSession->id, [$killZone1->id]);
            $liveSession->delete();
            $dungeonRoute->delete();
        }
    }

    // -------------------------------------------------------------------------
    // processResolvedKills — obsolete recompute
    // -------------------------------------------------------------------------

    #[Test]
    public function processResolvedKills_givenOffRouteKill_recomputesAndPersistsObsoleteEnemies(): void
    {
        // Arrange
        Event::fake([EnemyKilledEvent::class, OverpulledEnemyChangedEvent::class, RouteCorrectionEvent::class]);

        [$dungeonRoute, $liveSession, $killZone1] = $this->arrangeFreeholdSession();

        // The id divergence (live_session.id != dungeon_route_id) is required to exercise the
        // OverpulledEnemyService SQL fix (#3288). In a shared test DB this is naturally true.
        $this->assertNotSame($liveSession->id, $dungeonRoute->id);

        // Kill zone 2 (downstream) holds a skippable enemy that can be flagged obsolete
        $killZone2 = KillZone::factory()->create([
            'dungeon_route_id' => $dungeonRoute->id,
            'index'            => 2,
        ]);
        KillZoneEnemy::query()->create([
            'kill_zone_id' => $killZone2->id,
            'npc_id'       => self::SKIPPABLE_NPC_ID,
            'mdt_id'       => self::SKIPPABLE_MDT_ID,
            'enemy_id'     => self::SKIPPABLE_ENEMY_ID,
        ]);

        $offRouteEnemy = Enemy::findOrFail(self::OFF_ROUTE_ENEMY_ID);

        try {
            /** @var LiveSessionOverpullDetectionServiceInterface $service */
            $service = app()->make(LiveSessionOverpullDetectionServiceInterface::class);

            // Act: off-route kill (8 enemy forces from npc 127106) → overpull > 4 (skippable forces) → obsolete
            $service->processResolvedKills($liveSession, collect([$offRouteEnemy]), collect());

            // Assert: skippable enemy persisted as obsolete
            $this->assertDatabaseHas('live_session_obsolete_enemies', [
                'live_session_id' => $liveSession->id,
                'npc_id'          => self::SKIPPABLE_NPC_ID,
                'mdt_id'          => self::SKIPPABLE_MDT_ID,
            ]);

            // Assert: RouteCorrectionEvent dispatched
            Event::assertDispatched(RouteCorrectionEvent::class);
        } finally {
            $this->cleanupSession($liveSession->id, [$killZone1->id, $killZone2->id]);
            $liveSession->delete();
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function processResolvedKills_givenOverpullThenObsoleteEnemyKilled_recomputesObsoleteExcludingKilled(): void
    {
        // Arrange
        Event::fake([EnemyKilledEvent::class, OverpulledEnemyChangedEvent::class, RouteCorrectionEvent::class]);

        [$dungeonRoute, $liveSession, $killZone1] = $this->arrangeFreeholdSession();

        // KZ2/KZ3: one downstream skippable enemy (5 forces) each
        $killZone2 = KillZone::factory()->create(['dungeon_route_id' => $dungeonRoute->id, 'index' => 2]);
        $killZone3 = KillZone::factory()->create(['dungeon_route_id' => $dungeonRoute->id, 'index' => 3]);
        KillZoneEnemy::query()->create([
            'kill_zone_id' => $killZone2->id,
            'npc_id'       => self::SKIPPABLE_A_NPC_ID,
            'mdt_id'       => self::SKIPPABLE_A_MDT_ID,
            'enemy_id'     => self::SKIPPABLE_A_ENEMY_ID,
        ]);
        KillZoneEnemy::query()->create([
            'kill_zone_id' => $killZone3->id,
            'npc_id'       => self::SKIPPABLE_B_NPC_ID,
            'mdt_id'       => self::SKIPPABLE_B_MDT_ID,
            'enemy_id'     => self::SKIPPABLE_B_ENEMY_ID,
        ]);

        $offRouteEnemy   = Enemy::findOrFail(self::OFF_ROUTE_ENEMY_ID);
        $skippableEnemyA = Enemy::findOrFail(self::SKIPPABLE_A_ENEMY_ID);

        try {
            /** @var LiveSessionOverpullDetectionServiceInterface $service */
            $service = app()->make(LiveSessionOverpullDetectionServiceInterface::class);

            // Act 1: overpull (8 forces) marks the first downstream skippable enemy (A) obsolete
            $service->processResolvedKills($liveSession, collect([$offRouteEnemy]), collect());

            $this->assertDatabaseHas('live_session_obsolete_enemies', [
                'live_session_id' => $liveSession->id,
                'npc_id'          => self::SKIPPABLE_A_NPC_ID,
                'mdt_id'          => self::SKIPPABLE_A_MDT_ID,
            ]);

            // Act 2: the obsolete enemy A is killed anyway → it must drop out of obsolete, B takes its place
            $service->processResolvedKills($liveSession, collect([$skippableEnemyA]), collect());

            // Assert: A is killed and no longer obsolete; B is now obsolete instead
            $this->assertDatabaseHas('live_session_killed_enemies', [
                'live_session_id' => $liveSession->id,
                'npc_id'          => self::SKIPPABLE_A_NPC_ID,
                'mdt_id'          => self::SKIPPABLE_A_MDT_ID,
            ]);
            $this->assertDatabaseMissing('live_session_obsolete_enemies', [
                'live_session_id' => $liveSession->id,
                'npc_id'          => self::SKIPPABLE_A_NPC_ID,
                'mdt_id'          => self::SKIPPABLE_A_MDT_ID,
            ]);
            $this->assertDatabaseHas('live_session_obsolete_enemies', [
                'live_session_id' => $liveSession->id,
                'npc_id'          => self::SKIPPABLE_B_NPC_ID,
                'mdt_id'          => self::SKIPPABLE_B_MDT_ID,
            ]);
        } finally {
            $this->cleanupSession($liveSession->id, [$killZone1->id, $killZone2->id, $killZone3->id]);
            $liveSession->delete();
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function processResolvedKills_givenNoNewOverpullButObsoleteKilled_stillRecomputes(): void
    {
        // Arrange
        Event::fake([EnemyKilledEvent::class, OverpulledEnemyChangedEvent::class, RouteCorrectionEvent::class]);

        [$dungeonRoute, $liveSession, $killZone1] = $this->arrangeFreeholdSession();

        $killZone2 = KillZone::factory()->create(['dungeon_route_id' => $dungeonRoute->id, 'index' => 2]);
        $killZone3 = KillZone::factory()->create(['dungeon_route_id' => $dungeonRoute->id, 'index' => 3]);
        KillZoneEnemy::query()->create([
            'kill_zone_id' => $killZone2->id,
            'npc_id'       => self::SKIPPABLE_A_NPC_ID,
            'mdt_id'       => self::SKIPPABLE_A_MDT_ID,
            'enemy_id'     => self::SKIPPABLE_A_ENEMY_ID,
        ]);
        KillZoneEnemy::query()->create([
            'kill_zone_id' => $killZone3->id,
            'npc_id'       => self::SKIPPABLE_B_NPC_ID,
            'mdt_id'       => self::SKIPPABLE_B_MDT_ID,
            'enemy_id'     => self::SKIPPABLE_B_ENEMY_ID,
        ]);

        $offRouteEnemy   = Enemy::findOrFail(self::OFF_ROUTE_ENEMY_ID);
        $skippableEnemyA = Enemy::findOrFail(self::SKIPPABLE_A_ENEMY_ID);

        try {
            /** @var LiveSessionOverpullDetectionServiceInterface $service */
            $service = app()->make(LiveSessionOverpullDetectionServiceInterface::class);

            // Establish the overpull and initial obsolete (A) state
            $service->processResolvedKills($liveSession, collect([$offRouteEnemy]), collect());

            // Act: a chunk containing only an on-route kill (A) — no new overpull is recorded
            Event::fake([EnemyKilledEvent::class, OverpulledEnemyChangedEvent::class, RouteCorrectionEvent::class]);
            $service->processResolvedKills($liveSession, collect([$skippableEnemyA]), collect());

            // Assert: obsolete still gets recomputed (and broadcast) even though no new overpull occurred
            Event::assertDispatched(EnemyKilledEvent::class);
            Event::assertNotDispatched(OverpulledEnemyChangedEvent::class);
            Event::assertDispatched(RouteCorrectionEvent::class);
        } finally {
            $this->cleanupSession($liveSession->id, [$killZone1->id, $killZone2->id, $killZone3->id]);
            $liveSession->delete();
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function processResolvedKills_givenUnchangedNonEmptyObsoleteSet_doesNotRebroadcastRouteCorrection(): void
    {
        // Arrange
        Event::fake([EnemyKilledEvent::class, OverpulledEnemyChangedEvent::class, RouteCorrectionEvent::class]);

        [$dungeonRoute, $liveSession, $killZone1] = $this->arrangeFreeholdSession();

        // One downstream skippable enemy that will become (and stay) obsolete
        $killZone2 = KillZone::factory()->create(['dungeon_route_id' => $dungeonRoute->id, 'index' => 2]);
        KillZoneEnemy::query()->create([
            'kill_zone_id' => $killZone2->id,
            'npc_id'       => self::SKIPPABLE_NPC_ID,
            'mdt_id'       => self::SKIPPABLE_MDT_ID,
            'enemy_id'     => self::SKIPPABLE_ENEMY_ID,
        ]);

        $offRouteEnemy = Enemy::findOrFail(self::OFF_ROUTE_ENEMY_ID);

        try {
            /** @var LiveSessionOverpullDetectionServiceInterface $service */
            $service = app()->make(LiveSessionOverpullDetectionServiceInterface::class);

            // Establish a non-empty obsolete set
            $service->processResolvedKills($liveSession, collect([$offRouteEnemy]), collect());
            $this->assertDatabaseHas('live_session_obsolete_enemies', [
                'live_session_id' => $liveSession->id,
                'npc_id'          => self::SKIPPABLE_NPC_ID,
                'mdt_id'          => self::SKIPPABLE_MDT_ID,
            ]);

            // Act: process an on-route kill that does NOT change the obsolete set
            Event::fake([EnemyKilledEvent::class, OverpulledEnemyChangedEvent::class, RouteCorrectionEvent::class]);
            $service->processResolvedKills($liveSession, collect([Enemy::findOrFail(self::ON_ROUTE_ENEMY_ID)]), collect());

            // Assert: obsolete set is unchanged, so no RouteCorrectionEvent is re-broadcast
            Event::assertNotDispatched(RouteCorrectionEvent::class);
        } finally {
            $this->cleanupSession($liveSession->id, [$killZone1->id, $killZone2->id]);
            $liveSession->delete();
            $dungeonRoute->delete();
        }
    }

    // -------------------------------------------------------------------------
    // processResolvedKills — idempotency
    // -------------------------------------------------------------------------

    #[Test]
    public function processResolvedKills_givenSameKillsProcessedTwice_doesNotDuplicateRowsOrRebroadcast(): void
    {
        // Arrange
        Event::fake([EnemyKilledEvent::class, OverpulledEnemyChangedEvent::class, RouteCorrectionEvent::class]);

        [$dungeonRoute, $liveSession, $killZone1] = $this->arrangeFreeholdSession();
        $onRouteEnemy                             = Enemy::findOrFail(self::ON_ROUTE_ENEMY_ID);
        $offRouteEnemy                            = Enemy::findOrFail(self::OFF_ROUTE_ENEMY_ID);
        $kills                                    = collect([$onRouteEnemy, $offRouteEnemy]);

        try {
            /** @var LiveSessionOverpullDetectionServiceInterface $service */
            $service = app()->make(LiveSessionOverpullDetectionServiceInterface::class);

            // First run
            $service->processResolvedKills($liveSession, $kills, collect());

            $killedAfterFirst     = LiveSessionKilledEnemy::query()->where('live_session_id', $liveSession->id)->count();
            $overpulledAfterFirst = LiveSessionOverpulledEnemy::query()->where('live_session_id', $liveSession->id)->count();

            // Act — reset fake so we only track new events from the second run
            Event::fake([EnemyKilledEvent::class, OverpulledEnemyChangedEvent::class, RouteCorrectionEvent::class]);
            $service->processResolvedKills($liveSession, $kills, collect());

            $killedAfterSecond     = LiveSessionKilledEnemy::query()->where('live_session_id', $liveSession->id)->count();
            $overpulledAfterSecond = LiveSessionOverpulledEnemy::query()->where('live_session_id', $liveSession->id)->count();

            // Assert: counts unchanged
            $this->assertSame($killedAfterFirst, $killedAfterSecond);
            $this->assertSame($overpulledAfterFirst, $overpulledAfterSecond);

            // Assert: no events re-dispatched on the second run
            Event::assertNotDispatched(EnemyKilledEvent::class);
            Event::assertNotDispatched(OverpulledEnemyChangedEvent::class);
            Event::assertNotDispatched(RouteCorrectionEvent::class);
        } finally {
            $this->cleanupSession($liveSession->id, [$killZone1->id]);
            $liveSession->delete();
            $dungeonRoute->delete();
        }
    }

    // -------------------------------------------------------------------------
    // processResolvedKills — no kill zones
    // -------------------------------------------------------------------------

    #[Test]
    public function processResolvedKills_givenRouteWithNoKillZones_doesNotThrowAndSkipsOverpull(): void
    {
        // Arrange
        Event::fake([EnemyKilledEvent::class, OverpulledEnemyChangedEvent::class, RouteCorrectionEvent::class]);

        $dungeon = Dungeon::where('challenge_mode_id', self::FREEHOLD_CHALLENGE_MODE_ID)->firstOrFail();

        // Pin the route to the mapping version of the hardcoded enemy IDs rather than getCurrentMappingVersion()
        // to avoid divergence from a leaked, bumped Freehold mapping version (see MappingVersionFactory).
        $offRouteEnemy = Enemy::findOrFail(self::OFF_ROUTE_ENEMY_ID);

        /** @var DungeonRoute $dungeonRoute */
        $dungeonRoute = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $offRouteEnemy->mapping_version_id,
        ]);

        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create(['dungeon_route_id' => $dungeonRoute->id]);

        try {
            /** @var LiveSessionOverpullDetectionServiceInterface $service */
            $service = app()->make(LiveSessionOverpullDetectionServiceInterface::class);

            // Act: should not throw
            $service->processResolvedKills($liveSession, collect([$offRouteEnemy]), collect());

            // Assert: nothing persisted (no kill zones → no attribution possible)
            $this->assertSame(0, LiveSessionOverpulledEnemy::query()->where('live_session_id', $liveSession->id)->count());
            Event::assertNotDispatched(OverpulledEnemyChangedEvent::class);
        } finally {
            LiveSessionKilledEnemy::query()->where('live_session_id', $liveSession->id)->delete();
            LiveSessionOverpulledEnemy::query()->where('live_session_id', $liveSession->id)->delete();
            LiveSessionObsoleteEnemy::query()->where('live_session_id', $liveSession->id)->delete();
            LiveSessionInCombatEnemy::query()->where('live_session_id', $liveSession->id)->delete();
            $liveSession->delete();
            $dungeonRoute->delete();
        }
    }

    // -------------------------------------------------------------------------
    // processResolvedKills — in-combat tracking + current-pull seeding
    // -------------------------------------------------------------------------

    #[Test]
    public function processResolvedKills_givenInCombatEnemies_persistsRowsAndBroadcastsInCombatChangedOnceUntilSetChanges(): void
    {
        // Arrange
        Event::fake([EnemyKilledEvent::class, OverpulledEnemyChangedEvent::class, RouteCorrectionEvent::class, InCombatEnemiesChangedEvent::class]);

        [$dungeonRoute, $liveSession, $killZone1] = $this->arrangeFreeholdSession();
        $inCombatEnemy                            = Enemy::findOrFail(self::ON_ROUTE_ENEMY_ID);

        try {
            /** @var LiveSessionOverpullDetectionServiceInterface $service */
            $service = app()->make(LiveSessionOverpullDetectionServiceInterface::class);

            // Act: a chunk with no kills but an enemy in combat
            $service->processResolvedKills($liveSession, collect(), collect([$inCombatEnemy]));

            // Assert: persisted + broadcast
            $this->assertDatabaseHas('live_session_in_combat_enemies', [
                'live_session_id' => $liveSession->id,
                'npc_id'          => $inCombatEnemy->npc_id,
                'mdt_id'          => $inCombatEnemy->mdt_id,
            ]);
            Event::assertDispatched(InCombatEnemiesChangedEvent::class);

            // Act: same in-combat set again → no re-broadcast
            Event::fake([InCombatEnemiesChangedEvent::class]);
            $service->processResolvedKills($liveSession, collect(), collect([$inCombatEnemy]));
            Event::assertNotDispatched(InCombatEnemiesChangedEvent::class);

            // Act: clear the in-combat set → rows removed and a fresh broadcast of the (now empty) set
            Event::fake([InCombatEnemiesChangedEvent::class]);
            $service->processResolvedKills($liveSession, collect(), collect());
            Event::assertDispatched(InCombatEnemiesChangedEvent::class);
            $this->assertSame(0, LiveSessionInCombatEnemy::query()->where('live_session_id', $liveSession->id)->count());
        } finally {
            $this->cleanupSession($liveSession->id, [$killZone1->id]);
            $liveSession->delete();
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function processResolvedKills_givenOffRouteKillWithInCombatLeadingPull_attributesToInCombatPullNotFirst(): void
    {
        // Arrange
        Event::fake([EnemyKilledEvent::class, OverpulledEnemyChangedEvent::class, RouteCorrectionEvent::class, InCombatEnemiesChangedEvent::class]);

        [$dungeonRoute, $liveSession, $killZone1] = $this->arrangeFreeholdSession();

        // Kill zone 2 (downstream) holds an on-route enemy we are currently in combat with
        $killZone2 = KillZone::factory()->create(['dungeon_route_id' => $dungeonRoute->id, 'index' => 2]);
        KillZoneEnemy::query()->create([
            'kill_zone_id' => $killZone2->id,
            'npc_id'       => self::SKIPPABLE_B_NPC_ID,
            'mdt_id'       => self::SKIPPABLE_B_MDT_ID,
            'enemy_id'     => self::SKIPPABLE_B_ENEMY_ID,
        ]);

        $offRouteEnemy        = Enemy::findOrFail(self::OFF_ROUTE_ENEMY_ID);
        $inCombatLeadingEnemy = Enemy::findOrFail(self::SKIPPABLE_B_ENEMY_ID);

        try {
            /** @var LiveSessionOverpullDetectionServiceInterface $service */
            $service = app()->make(LiveSessionOverpullDetectionServiceInterface::class);

            // Act: no on-route kill in this chunk to anchor the pull, but we ARE in combat with a kill zone 2
            // enemy — so the overpull must attribute to kill zone 2, not the first kill zone.
            $service->processResolvedKills($liveSession, collect([$offRouteEnemy]), collect([$inCombatLeadingEnemy]));

            // Assert: attributed to kill zone 2 (the in-combat leading edge), not kill zone 1
            $this->assertDatabaseHas('live_session_overpulled_enemies', [
                'live_session_id' => $liveSession->id,
                'npc_id'          => $offRouteEnemy->npc_id,
                'mdt_id'          => $offRouteEnemy->mdt_id,
                'kill_zone_id'    => $killZone2->id,
            ]);
        } finally {
            $this->cleanupSession($liveSession->id, [$killZone1->id, $killZone2->id]);
            $liveSession->delete();
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function processResolvedKills_givenOffRouteKillInLaterPassWithNoInCombat_attributesToLastKilledPullNotFirst(): void
    {
        // This is the reported cross-pass bug: a buffer chunk that contains only an off-route kill (no
        // on-route kill, nothing in combat) must attribute the overpull to the pull we last killed in -
        // not reset to the first kill zone.
        Event::fake([EnemyKilledEvent::class, OverpulledEnemyChangedEvent::class, RouteCorrectionEvent::class, InCombatEnemiesChangedEvent::class]);

        [$dungeonRoute, $liveSession, $killZone1] = $this->arrangeFreeholdSession();

        // Kill zone 2 (downstream) holds an on-route enemy we will kill in the first pass
        $killZone2 = KillZone::factory()->create(['dungeon_route_id' => $dungeonRoute->id, 'index' => 2]);
        KillZoneEnemy::query()->create([
            'kill_zone_id' => $killZone2->id,
            'npc_id'       => self::SKIPPABLE_B_NPC_ID,
            'mdt_id'       => self::SKIPPABLE_B_MDT_ID,
            'enemy_id'     => self::SKIPPABLE_B_ENEMY_ID,
        ]);

        $kz2Enemy      = Enemy::findOrFail(self::SKIPPABLE_B_ENEMY_ID);
        $offRouteEnemy = Enemy::findOrFail(self::OFF_ROUTE_ENEMY_ID);

        try {
            /** @var LiveSessionOverpullDetectionServiceInterface $service */
            $service = app()->make(LiveSessionOverpullDetectionServiceInterface::class);

            // Pass 1: kill the kill-zone-2 enemy (persists it as killed)
            $service->processResolvedKills($liveSession, collect([$kz2Enemy]), collect());

            // Pass 2: a separate chunk with only an off-route kill and nothing in combat. The current pull
            // must be seeded from the persisted kill (kill zone 2), not reset to the first kill zone.
            $service->processResolvedKills($liveSession, collect([$offRouteEnemy]), collect());

            // Assert: attributed to kill zone 2 (last killed pull), not kill zone 1 (first)
            $this->assertDatabaseHas('live_session_overpulled_enemies', [
                'live_session_id' => $liveSession->id,
                'npc_id'          => $offRouteEnemy->npc_id,
                'mdt_id'          => $offRouteEnemy->mdt_id,
                'kill_zone_id'    => $killZone2->id,
            ]);
        } finally {
            $this->cleanupSession($liveSession->id, [$killZone1->id, $killZone2->id]);
            $liveSession->delete();
            $dungeonRoute->delete();
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Create a Freehold DungeonRoute + LiveSession + KillZone 1 containing the ON_ROUTE_ENEMY.
     *
     * @return array{DungeonRoute, LiveSession, KillZone}
     */
    private function arrangeFreeholdSession(): array
    {
        $dungeon = Dungeon::where('challenge_mode_id', self::FREEHOLD_CHALLENGE_MODE_ID)->firstOrFail();

        // Pin the route to the mapping version of the hardcoded enemy IDs. Using getCurrentMappingVersion()
        // is brittle: another test leaking a bumped Freehold mapping version (see MappingVersionFactory) would
        // make the route resolve (npc_id, mdt_id) to a different enemies.id than the hardcoded ones below.
        $onRouteEnemy = Enemy::findOrFail(self::ON_ROUTE_ENEMY_ID);

        /** @var DungeonRoute $dungeonRoute */
        $dungeonRoute = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $onRouteEnemy->mapping_version_id,
        ]);

        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create(['dungeon_route_id' => $dungeonRoute->id]);

        $killZone1 = KillZone::factory()->create([
            'dungeon_route_id' => $dungeonRoute->id,
            'index'            => 1,
        ]);

        KillZoneEnemy::query()->create([
            'kill_zone_id' => $killZone1->id,
            'npc_id'       => $onRouteEnemy->npc_id,
            'mdt_id'       => $onRouteEnemy->mdt_id,
            'enemy_id'     => $onRouteEnemy->id,
        ]);

        return [$dungeonRoute, $liveSession, $killZone1];
    }

    /**
     * @param int[] $killZoneIds
     */
    private function cleanupSession(int $liveSessionId, array $killZoneIds): void
    {
        LiveSessionKilledEnemy::query()->where('live_session_id', $liveSessionId)->delete();
        LiveSessionOverpulledEnemy::query()->where('live_session_id', $liveSessionId)->delete();
        LiveSessionObsoleteEnemy::query()->where('live_session_id', $liveSessionId)->delete();
        LiveSessionInCombatEnemy::query()->where('live_session_id', $liveSessionId)->delete();
        KillZoneEnemy::query()->whereIn('kill_zone_id', $killZoneIds)->delete();

        foreach ($killZoneIds as $killZoneId) {
            KillZone::query()->where('id', $killZoneId)->delete();
        }
    }
}
