<?php

namespace Tests\Feature\Jobs\LiveSession;

use App\Events\LiveSession\LiveSessionStateChangedEvent;
use App\Jobs\LiveSession\ProcessLiveSessionCombatLogBuffer;
use App\Logic\CombatLog\CombatEvents\Advanced\AdvancedDataInterface;
use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\CombatEvents\GenericData\GenericDataInterface;
use App\Logic\CombatLog\Guid\Guid;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemies\OverpulledEnemy;
use App\Models\Enemy;
use App\Models\KillZone\KillZone;
use App\Models\KillZone\KillZoneEnemy;
use App\Models\LiveSession\LiveSession;
use App\Models\LiveSession\LiveSessionCombatLogBatch;
use App\Models\LiveSession\LiveSessionCombatLogBuffer;
use App\Models\LiveSession\LiveSessionEnemyState;
use App\Models\LiveSession\LiveSessionEnemyStateType;
use App\Models\LiveSession\LiveSessionOverpulledEnemy;
use App\Models\LiveSession\LiveSessionPlayerPosition;
use App\Models\User;
use App\Repositories\Database\LiveSession\LiveSessionCombatLogBatchRepository;
use App\Repositories\Database\LiveSession\LiveSessionCombatLogBufferRepository;
use App\Service\CombatLog\CombatLogServiceInterface;
use App\Service\LiveSession\LiveSessionBufferProcessingService;
use App\Service\LiveSession\LiveSessionBufferProcessingServiceInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCases\PublicTestCase;

#[Group('LiveSession')]
#[Group('ProcessLiveSessionCombatLogBuffer')]
final class ProcessLiveSessionCombatLogBufferTest extends PublicTestCase
{
    private const string PIT_OF_SARON_EVENTS_FILE = '/CombatLogs/mn_s1/WoWCombatLog-061126_213150_10_pit-of-saron_events.txt';

    private const int PIT_OF_SARON_CHALLENGE_MODE_ID = 556;

    /**
     * NPC IDs that appear in the PIT_OF_SARON_EVENTS_FILE as UNIT_DIED events.
     * Assigning all of their enemy instances to a kill zone ensures at least one
     * kill from the real log is classified as on-route by LiveSessionOverpullDetectionService.
     *
     * @var int[]
     */
    private const array PIT_OF_SARON_KILLED_NPC_IDS = [252558, 252602, 252603, 252559];

    // -------------------------------------------------------------------------
    // Job dispatch
    // -------------------------------------------------------------------------

    #[Test]
    public function store_givenValidBuffer_dispatchesJob(): void
    {
        // Arrange
        Queue::fake();

        /** @var User $user */
        $user = User::findOrFail(1);
        $this->actingAs($user);

        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();

        try {
            // Act
            $this->postJson(
                route('api.v1.combatlog.livesession.events.store', ['liveSession' => $liveSession->public_key]),
                ['lines' => ['6/11/2026 22:22:55.5142  CHALLENGE_MODE_START,"Pit of Saron",658,556,10,[162,9,10]']],
            )->assertOk();

            // Assert
            Queue::assertPushed(ProcessLiveSessionCombatLogBuffer::class, function (ProcessLiveSessionCombatLogBuffer $job) use ($liveSession) {
                return $job->liveSessionId === $liveSession->id;
            });
        } finally {
            $this->cleanupCombatLog($liveSession->id);
            $liveSession->delete();
            $liveSession->dungeonRoute?->delete();
        }
    }

    // -------------------------------------------------------------------------
    // Service: processBuffer
    // -------------------------------------------------------------------------

    #[Test]
    public function processBuffer_givenOnlyACheckpointAndNoPendingBatch_parsesNothing(): void
    {
        // Arrange
        [$liveSession, $checkpoint] = $this->arrangeCheckpointWithoutPendingBatches();

        $combatLogService = $this->createMockPublic(CombatLogServiceInterface::class);
        $combatLogService->expects($this->never())->method('parseCombatLogStreaming');
        app()->instance(CombatLogServiceInterface::class, $combatLogService);

        try {
            // Act
            app()->make(LiveSessionBufferProcessingServiceInterface::class)->processBuffer($liveSession);

            // Assert
            $this->assertSame($checkpoint->buffer, $checkpoint->fresh()->buffer);
        } finally {
            $this->cleanupCombatLog($liveSession->id);
            $liveSession->delete();
            $liveSession->dungeonRoute?->delete();
        }
    }

    #[Test]
    public function processBuffer_givenACheckpointAndAPendingBatch_parsesThem(): void
    {
        // Arrange - the same setup as above, plus one pending batch
        [$liveSession] = $this->arrangeCheckpointWithoutPendingBatches();
        $this->storeBatch($liveSession, ['6/11/2026 22:22:55.5142  CHALLENGE_MODE_START,"Pit of Saron",658,556,10,[162,9,10]']);

        $combatLogService = $this->createMockPublic(CombatLogServiceInterface::class);
        $combatLogService->expects($this->once())->method('parseCombatLogStreaming');
        app()->instance(CombatLogServiceInterface::class, $combatLogService);

        try {
            // Act
            app()->make(LiveSessionBufferProcessingServiceInterface::class)->processBuffer($liveSession);

            // Assert - expectations above
        } finally {
            $this->cleanupLiveSessionEnemyState($liveSession->id, []);
            LiveSessionPlayerPosition::query()->where('live_session_id', $liveSession->id)->delete();
            $this->cleanupCombatLog($liveSession->id);
            $liveSession->delete();
            $liveSession->dungeonRoute?->delete();
        }
    }

    #[Test]
    public function processBuffer_givenRealPitOfSaronLog_persistsKilledEnemies(): void
    {
        if (!file_exists(base_path('tests') . self::PIT_OF_SARON_EVENTS_FILE)) {
            $this->markTestSkipped('Pit of Saron events file not found');
        }

        // Arrange
        Event::fake([LiveSessionStateChangedEvent::class]);

        $dungeon        = Dungeon::where('challenge_mode_id', self::PIT_OF_SARON_CHALLENGE_MODE_ID)->firstOrFail();
        $mappingVersion = $dungeon->getCurrentMappingVersion();

        if ($mappingVersion === null) {
            $this->markTestSkipped('No current mapping version for Pit of Saron');
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

        $killZone = $this->createPitOfSaronKillZone($dungeonRoute);

        $this->storeBatch($liveSession, file(base_path('tests') . self::PIT_OF_SARON_EVENTS_FILE, FILE_IGNORE_NEW_LINES), 1);

        try {
            /** @var LiveSessionBufferProcessingServiceInterface $service */
            $service = app()->make(LiveSessionBufferProcessingServiceInterface::class);
            $liveSession->load(['dungeonRoute.mappingVersion.dungeon.floors']);

            // Act
            $service->processBuffer($liveSession);

            // Assert: some enemies should have been killed (on-route)
            $killedCount = LiveSessionEnemyState::query()
                ->where('state', LiveSessionEnemyStateType::Killed)
                ->where('live_session_id', $liveSession->id)
                ->count();

            $this->assertGreaterThan(0, $killedCount, 'Expected at least one killed enemy to be persisted');
        } finally {
            $this->cleanupLiveSessionEnemyState($liveSession->id, [$killZone->id]);
            LiveSessionPlayerPosition::query()->where('live_session_id', $liveSession->id)->delete();
            LiveSessionEnemyState::query()->where('live_session_id', $liveSession->id)->delete();
            $this->cleanupCombatLog($liveSession->id);
            $liveSession->delete();
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function processBuffer_givenRealPitOfSaronLog_persistsPlayerClassAndSpecialization(): void
    {
        if (!file_exists(base_path('tests') . self::PIT_OF_SARON_EVENTS_FILE)) {
            $this->markTestSkipped('Pit of Saron events file not found');
        }

        // Arrange
        Event::fake([LiveSessionStateChangedEvent::class]);

        $dungeon        = Dungeon::where('challenge_mode_id', self::PIT_OF_SARON_CHALLENGE_MODE_ID)->firstOrFail();
        $mappingVersion = $dungeon->getCurrentMappingVersion();

        if ($mappingVersion === null) {
            $this->markTestSkipped('No current mapping version for Pit of Saron');
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

        $this->storeBatch($liveSession, file(base_path('tests') . self::PIT_OF_SARON_EVENTS_FILE, FILE_IGNORE_NEW_LINES), 1);

        try {
            /** @var LiveSessionBufferProcessingServiceInterface $service */
            $service = app()->make(LiveSessionBufferProcessingServiceInterface::class);
            $liveSession->load(['dungeonRoute.mappingVersion.dungeon.floors']);

            // Act
            $service->processBuffer($liveSession);

            // Assert: at least one player position was resolved with a class & specialization from COMBATANT_INFO
            $withSpec = LiveSessionPlayerPosition::query()
                ->where('live_session_id', $liveSession->id)
                ->whereNotNull('class_id')
                ->whereNotNull('specialization_id')
                ->count();

            $this->assertGreaterThan(0, $withSpec, 'Expected at least one player position to have a resolved class & specialization');
        } finally {
            LiveSessionPlayerPosition::query()->where('live_session_id', $liveSession->id)->delete();
            LiveSessionEnemyState::query()->where('live_session_id', $liveSession->id)->delete();
            $this->cleanupCombatLog($liveSession->id);
            $liveSession->delete();
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function processBuffer_givenARunThatCrashedBeforeCommitting_rerunDoesNotDuplicateKills(): void
    {
        if (!file_exists(base_path('tests') . self::PIT_OF_SARON_EVENTS_FILE)) {
            $this->markTestSkipped('Pit of Saron events file not found');
        }

        // Arrange
        Event::fake([LiveSessionStateChangedEvent::class]);

        $dungeon        = Dungeon::where('challenge_mode_id', self::PIT_OF_SARON_CHALLENGE_MODE_ID)->firstOrFail();
        $mappingVersion = $dungeon->getCurrentMappingVersion();

        if ($mappingVersion === null) {
            $this->markTestSkipped('No current mapping version for Pit of Saron');
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

        $killZone = $this->createPitOfSaronKillZone($dungeonRoute);

        $this->storeBatch($liveSession, file(base_path('tests') . self::PIT_OF_SARON_EVENTS_FILE, FILE_IGNORE_NEW_LINES), 1);

        try {
            /** @var LiveSessionBufferProcessingServiceInterface $service */
            $service = app()->make(LiveSessionBufferProcessingServiceInterface::class);
            $liveSession->load(['dungeonRoute.mappingVersion.dungeon.floors']);

            // Act — the first run writes the state, then dies before its checkpoint commits; the next run redoes it
            $this->processBufferCrashingBeforeTheCheckpointCommits($liveSession);
            $countAfterFirst = LiveSessionEnemyState::query()
                ->where('state', LiveSessionEnemyStateType::Killed)
                ->where('live_session_id', $liveSession->id)
                ->count();
            $pendingAfterCrash = LiveSessionCombatLogBatch::query()
                ->where('live_session_id', $liveSession->id)
                ->whereNull('consumed_at')
                ->count();

            $rerunException = null;

            try {
                $service->processBuffer($liveSession);
            } catch (QueryException $queryException) {
                $rerunException = $queryException;
            }
            $countAfterSecond = LiveSessionEnemyState::query()
                ->where('state', LiveSessionEnemyStateType::Killed)
                ->where('live_session_id', $liveSession->id)
                ->count();

            // Assert: the crashed run consumed nothing, and redoing it does not create duplicates
            $this->assertNull($rerunException, 'Redoing the kills the crashed run already recorded must be a no-op, not a failing insert');
            $this->assertSame(1, $pendingAfterCrash, 'A run that crashed before committing must leave its batch pending');
            $this->assertFalse(LiveSessionCombatLogBatch::query()
                ->where('live_session_id', $liveSession->id)
                ->whereNull('consumed_at')
                ->exists(), 'The rerun must consume the batch');
            $this->assertGreaterThan(0, $countAfterFirst);
            $this->assertSame($countAfterFirst, $countAfterSecond);
        } finally {
            $this->cleanupLiveSessionEnemyState($liveSession->id, [$killZone->id]);
            LiveSessionPlayerPosition::query()->where('live_session_id', $liveSession->id)->delete();
            LiveSessionEnemyState::query()->where('live_session_id', $liveSession->id)->delete();
            $this->cleanupCombatLog($liveSession->id);
            $liveSession->delete();
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function processBuffer_givenLogSplitIntoChunks_accumulatesKillsToMatchFullLog(): void
    {
        if (!file_exists(base_path('tests') . self::PIT_OF_SARON_EVENTS_FILE)) {
            $this->markTestSkipped('Pit of Saron events file not found');
        }

        // Arrange
        Event::fake([LiveSessionStateChangedEvent::class]);

        $dungeon        = Dungeon::where('challenge_mode_id', self::PIT_OF_SARON_CHALLENGE_MODE_ID)->firstOrFail();
        $mappingVersion = $dungeon->getCurrentMappingVersion();

        if ($mappingVersion === null) {
            $this->markTestSkipped('No current mapping version for Pit of Saron');
        }

        $allLines = explode("\n", file_get_contents(base_path('tests') . self::PIT_OF_SARON_EVENTS_FILE));

        // Full-log run in a separate session
        /** @var DungeonRoute $fullRoute */
        $fullRoute = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
        ]);
        /** @var LiveSession $fullSession */
        $fullSession  = LiveSession::factory()->create(['dungeon_route_id' => $fullRoute->id]);
        $fullKillZone = $this->createPitOfSaronKillZone($fullRoute);
        $this->storeBatch($fullSession, $allLines, 1);

        // Chunked run in another session — split roughly in half
        /** @var DungeonRoute $chunkedRoute */
        $chunkedRoute = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
        ]);
        /** @var LiveSession $chunkedSession */
        $chunkedSession  = LiveSession::factory()->create(['dungeon_route_id' => $chunkedRoute->id]);
        $chunkedKillZone = $this->createPitOfSaronKillZone($chunkedRoute);

        $midpoint   = (int)(count($allLines) / 2);
        $firstHalf  = array_slice($allLines, 0, $midpoint);
        $secondHalf = array_slice($allLines, $midpoint);

        $this->storeBatch($chunkedSession, $firstHalf, 1);

        try {
            /** @var LiveSessionBufferProcessingServiceInterface $service */
            $service = app()->make(LiveSessionBufferProcessingServiceInterface::class);

            // Run full log
            $fullSession->load(['dungeonRoute.mappingVersion.dungeon.floors']);
            $service->processBuffer($fullSession);
            $fullKillCount = LiveSessionEnemyState::query()
                ->where('state', LiveSessionEnemyStateType::Killed)
                ->where('live_session_id', $fullSession->id)
                ->count();

            // Run first chunk
            $chunkedSession->load(['dungeonRoute.mappingVersion.dungeon.floors']);
            $service->processBuffer($chunkedSession);

            // Second batch: replayed behind the checkpoint the first run left behind
            $this->storeBatch($chunkedSession, $secondHalf, 2);

            $chunkedSession->load(['dungeonRoute.mappingVersion.dungeon.floors']);
            $service->processBuffer($chunkedSession);

            $chunkedKillCount = LiveSessionEnemyState::query()
                ->where('state', LiveSessionEnemyStateType::Killed)
                ->where('live_session_id', $chunkedSession->id)
                ->count();

            // Assert: chunked processing arrives at the same kill count as one-shot processing
            $this->assertGreaterThan(0, $fullKillCount);
            $this->assertSame($fullKillCount, $chunkedKillCount);
        } finally {
            $sessionIds  = [$fullSession->id, $chunkedSession->id];
            $killZoneIds = [$fullKillZone->id, $chunkedKillZone->id];

            $this->cleanupLiveSessionEnemyState($sessionIds, $killZoneIds);
            LiveSessionPlayerPosition::query()->whereIn('live_session_id', $sessionIds)->delete();
            $this->cleanupCombatLog($sessionIds);
            $fullSession->delete();
            $chunkedSession->delete();
            $fullRoute->delete();
            $chunkedRoute->delete();
        }
    }

    #[Test]
    public function processBuffer_givenChunksAppendedToReducedBuffer_matchesFullLogKills(): void
    {
        if (!file_exists(base_path('tests') . self::PIT_OF_SARON_EVENTS_FILE)) {
            $this->markTestSkipped('Pit of Saron events file not found');
        }

        // Arrange
        Event::fake([LiveSessionStateChangedEvent::class]);

        $dungeon        = Dungeon::where('challenge_mode_id', self::PIT_OF_SARON_CHALLENGE_MODE_ID)->firstOrFail();
        $mappingVersion = $dungeon->getCurrentMappingVersion();

        if ($mappingVersion === null) {
            $this->markTestSkipped('No current mapping version for Pit of Saron');
        }

        $allLines = explode("\n", file_get_contents(base_path('tests') . self::PIT_OF_SARON_EVENTS_FILE));

        /** @var DungeonRoute $fullRoute */
        $fullRoute = DungeonRoute::factory()->create(['dungeon_id' => $dungeon->id, 'mapping_version_id' => $mappingVersion->id]);
        /** @var LiveSession $fullSession */
        $fullSession  = LiveSession::factory()->create(['dungeon_route_id' => $fullRoute->id]);
        $fullKillZone = $this->createPitOfSaronKillZone($fullRoute);
        $this->storeBatch($fullSession, $allLines, 1);

        /** @var DungeonRoute $chunkedRoute */
        $chunkedRoute = DungeonRoute::factory()->create(['dungeon_id' => $dungeon->id, 'mapping_version_id' => $mappingVersion->id]);
        /** @var LiveSession $chunkedSession */
        $chunkedSession  = LiveSession::factory()->create(['dungeon_route_id' => $chunkedRoute->id]);
        $chunkedKillZone = $this->createPitOfSaronKillZone($chunkedRoute);

        try {
            /** @var LiveSessionBufferProcessingServiceInterface $service */
            $service = app()->make(LiveSessionBufferProcessingServiceInterface::class);

            // One-shot run
            $fullSession->load(['dungeonRoute.mappingVersion.dungeon.floors']);
            $service->processBuffer($fullSession);
            $fullKillCount = $fullSession->enemyStates()->where('state', LiveSessionEnemyStateType::Killed)->count();

            // Incremental run: feed the log in chunks, each one stored as a batch the way the live controller
            // stores them and replayed behind the *reduced* checkpoint the previous processBuffer() left behind.
            // This is what proves a kill spanning chunks is not lost by the reduction.
            $chunks = array_chunk($allLines, (int)ceil(count($allLines) / 5));
            foreach ($chunks as $index => $chunk) {
                $this->storeBatch($chunkedSession, $chunk, $index + 1);

                $chunkedSession->load(['dungeonRoute.mappingVersion.dungeon.floors']);
                $service->processBuffer($chunkedSession);
            }

            $chunkedKillCount = $chunkedSession->enemyStates()->where('state', LiveSessionEnemyStateType::Killed)->count();

            // Assert
            $this->assertGreaterThan(0, $fullKillCount);
            $this->assertSame($fullKillCount, $chunkedKillCount);

            // Player positions are persisted per chunk from the freshly ingested lines, so they survive
            // even though player movement is never carried forward in the reduced buffer.
            $this->assertGreaterThan(
                0,
                LiveSessionPlayerPosition::query()->where('live_session_id', $chunkedSession->id)->count(),
                'Expected player positions to be persisted across chunked processing',
            );
        } finally {
            $sessionIds  = [$fullSession->id, $chunkedSession->id];
            $killZoneIds = [$fullKillZone->id, $chunkedKillZone->id];

            $this->cleanupLiveSessionEnemyState($sessionIds, $killZoneIds);
            LiveSessionPlayerPosition::query()->whereIn('live_session_id', $sessionIds)->delete();
            $this->cleanupCombatLog($sessionIds);
            $fullSession->delete();
            $chunkedSession->delete();
            $fullRoute->delete();
            $chunkedRoute->delete();
        }
    }

    // -------------------------------------------------------------------------
    // Service: processBuffer — event dispatching
    // -------------------------------------------------------------------------

    #[Test]
    public function processBuffer_givenRealPitOfSaronLog_broadcastsOneStateWithTheKilledEnemiesAndPlayers(): void
    {
        if (!file_exists(base_path('tests') . self::PIT_OF_SARON_EVENTS_FILE)) {
            $this->markTestSkipped('Pit of Saron events file not found');
        }

        // Arrange
        Event::fake([LiveSessionStateChangedEvent::class]);

        $dungeon        = Dungeon::where('challenge_mode_id', self::PIT_OF_SARON_CHALLENGE_MODE_ID)->firstOrFail();
        $mappingVersion = $dungeon->getCurrentMappingVersion();

        if ($mappingVersion === null) {
            $this->markTestSkipped('No current mapping version for Pit of Saron');
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

        $killZone = $this->createPitOfSaronKillZone($dungeonRoute);

        $this->storeBatch($liveSession, file(base_path('tests') . self::PIT_OF_SARON_EVENTS_FILE, FILE_IGNORE_NEW_LINES), 1);

        try {
            /** @var LiveSessionBufferProcessingServiceInterface $service */
            $service = app()->make(LiveSessionBufferProcessingServiceInterface::class);
            $liveSession->load(['dungeonRoute.mappingVersion.dungeon.floors']);

            // Act
            $service->processBuffer($liveSession);

            // Assert
            Event::assertDispatchedTimes(LiveSessionStateChangedEvent::class, 1);
            Event::assertDispatched(
                LiveSessionStateChangedEvent::class,
                static fn(LiveSessionStateChangedEvent $event): bool => count($event->broadcastWith()['state']['killed']) > 0
                    && count($event->broadcastWith()['state']['players']) > 0,
            );
        } finally {
            $this->cleanupLiveSessionEnemyState($liveSession->id, [$killZone->id]);
            LiveSessionPlayerPosition::query()->where('live_session_id', $liveSession->id)->delete();
            LiveSessionEnemyState::query()->where('live_session_id', $liveSession->id)->delete();
            $this->cleanupCombatLog($liveSession->id);
            $liveSession->delete();
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function processBuffer_givenAPassThatChangesNothing_broadcastsNoNewState(): void
    {
        if (!file_exists(base_path('tests') . self::PIT_OF_SARON_EVENTS_FILE)) {
            $this->markTestSkipped('Pit of Saron events file not found');
        }

        // Arrange
        Event::fake([LiveSessionStateChangedEvent::class]);

        [$dungeonRoute, $liveSession, $killZone] = $this->arrangePitOfSaronSessionWithTheFullLog();

        try {
            /** @var LiveSessionBufferProcessingServiceInterface $service */
            $service = app()->make(LiveSessionBufferProcessingServiceInterface::class);
            $service->processBuffer($liveSession);
            $this->storeBatch($liveSession, $this->getPitOfSaronLines(-1), 2);

            // Act
            $service->processBuffer($liveSession);

            // Assert
            Event::assertDispatchedTimes(LiveSessionStateChangedEvent::class, 1);
            $this->assertSame(0, LiveSessionCombatLogBatch::query()->where('live_session_id', $liveSession->id)->whereNull('consumed_at')->count());
        } finally {
            $this->cleanupPitOfSaronSession($dungeonRoute, $liveSession, $killZone);
        }
    }

    #[Test]
    public function processBuffer_givenTheRememberedStateWasLost_broadcastsTheUnchangedStateOnceMore(): void
    {
        if (!file_exists(base_path('tests') . self::PIT_OF_SARON_EVENTS_FILE)) {
            $this->markTestSkipped('Pit of Saron events file not found');
        }

        // Arrange
        Event::fake([LiveSessionStateChangedEvent::class]);

        [$dungeonRoute, $liveSession, $killZone] = $this->arrangePitOfSaronSessionWithTheFullLog();

        try {
            /** @var LiveSessionBufferProcessingServiceInterface $service */
            $service = app()->make(LiveSessionBufferProcessingServiceInterface::class);
            $service->processBuffer($liveSession);
            Cache::flush();
            $this->storeBatch($liveSession, $this->getPitOfSaronLines(-1), 2);

            // Act
            $service->processBuffer($liveSession);

            // Assert
            Event::assertDispatchedTimes(LiveSessionStateChangedEvent::class, 2);
        } finally {
            $this->cleanupPitOfSaronSession($dungeonRoute, $liveSession, $killZone);
        }
    }

    #[Test]
    public function processBuffer_givenAPassThatKillsMoreEnemies_broadcastsTheNewState(): void
    {
        if (!file_exists(base_path('tests') . self::PIT_OF_SARON_EVENTS_FILE)) {
            $this->markTestSkipped('Pit of Saron events file not found');
        }

        // Arrange
        Event::fake([LiveSessionStateChangedEvent::class]);

        [$dungeonRoute, $liveSession, $killZone] = $this->arrangePitOfSaronSession();
        $lines                                   = $this->getPitOfSaronLines();
        $this->storeBatch($liveSession, array_slice($lines, 0, intdiv(count($lines), 2)), 1);

        try {
            /** @var LiveSessionBufferProcessingServiceInterface $service */
            $service = app()->make(LiveSessionBufferProcessingServiceInterface::class);
            $service->processBuffer($liveSession);
            $this->storeBatch($liveSession, array_slice($lines, intdiv(count($lines), 2)), 2);

            // Act
            $service->processBuffer($liveSession);

            // Assert
            $killedCounts = Event::dispatched(LiveSessionStateChangedEvent::class)
                ->map(static fn(array $arguments): int => count($arguments[0]->broadcastWith()['state']['killed']))
                ->values()
                ->all();
            $this->assertCount(2, $killedCounts);
            $this->assertGreaterThan($killedCounts[0], $killedCounts[1]);
        } finally {
            $this->cleanupPitOfSaronSession($dungeonRoute, $liveSession, $killZone);
        }
    }

    #[Test]
    public function processBuffer_givenARunThatCrashedBeforeCommitting_rerunBroadcastsNoNewState(): void
    {
        if (!file_exists(base_path('tests') . self::PIT_OF_SARON_EVENTS_FILE)) {
            $this->markTestSkipped('Pit of Saron events file not found');
        }

        // Arrange — fake all live-session broadcasts so the first run does not need a live Reverb/Pusher connection
        Event::fake([LiveSessionStateChangedEvent::class]);

        $dungeon        = Dungeon::where('challenge_mode_id', self::PIT_OF_SARON_CHALLENGE_MODE_ID)->firstOrFail();
        $mappingVersion = $dungeon->getCurrentMappingVersion();

        if ($mappingVersion === null) {
            $this->markTestSkipped('No current mapping version for Pit of Saron');
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

        $killZone = $this->createPitOfSaronKillZone($dungeonRoute);

        $this->storeBatch($liveSession, file(base_path('tests') . self::PIT_OF_SARON_EVENTS_FILE, FILE_IGNORE_NEW_LINES), 1);

        try {
            /** @var LiveSessionBufferProcessingServiceInterface $service */
            $service = app()->make(LiveSessionBufferProcessingServiceInterface::class);
            $liveSession->load(['dungeonRoute.mappingVersion.dungeon.floors']);

            // First run populates the DB, then dies before its checkpoint commits
            $this->processBufferCrashingBeforeTheCheckpointCommits($liveSession);
            $countAfterFirst = LiveSessionEnemyState::query()
                ->where('state', LiveSessionEnemyStateType::Killed)
                ->where('live_session_id', $liveSession->id)
                ->count();

            // Act
            $service->processBuffer($liveSession);

            // Assert — the crashed run already broadcast this state; the rerun replayed the batch (and consumed it)
            // without producing a different one
            Event::assertDispatchedTimes(LiveSessionStateChangedEvent::class, 1);
            $this->assertGreaterThan(0, $countAfterFirst);
            $this->assertSame($countAfterFirst, LiveSessionEnemyState::query()
                ->where('state', LiveSessionEnemyStateType::Killed)
                ->where('live_session_id', $liveSession->id)
                ->count());
            $this->assertNotNull(LiveSessionCombatLogBatch::query()->where('live_session_id', $liveSession->id)->value('consumed_at'));
        } finally {
            $this->cleanupLiveSessionEnemyState($liveSession->id, [$killZone->id]);
            LiveSessionPlayerPosition::query()->where('live_session_id', $liveSession->id)->delete();
            LiveSessionEnemyState::query()->where('live_session_id', $liveSession->id)->delete();
            $this->cleanupCombatLog($liveSession->id);
            $liveSession->delete();
            $dungeonRoute->delete();
        }
    }

    // -------------------------------------------------------------------------
    // Job handle
    // -------------------------------------------------------------------------

    #[Test]
    public function handle_givenExpiredLiveSession_doesNotCallService(): void
    {
        // Arrange
        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->expired()->create();

        $service = $this->createMockPublic(LiveSessionBufferProcessingServiceInterface::class);
        $service->expects($this->never())->method('processBuffer');
        app()->instance(LiveSessionBufferProcessingServiceInterface::class, $service);

        try {
            // Act
            $job = new ProcessLiveSessionCombatLogBuffer($liveSession->id);
            app()->call([$job, 'handle']);

            // Assert — expectations above
        } finally {
            $liveSession->delete();
            $liveSession->dungeonRoute?->delete();
        }
    }

    #[Test]
    public function handle_givenNonExistentLiveSession_doesNotCallService(): void
    {
        // Arrange
        $service = $this->createMockPublic(LiveSessionBufferProcessingServiceInterface::class);
        $service->expects($this->never())->method('processBuffer');
        app()->instance(LiveSessionBufferProcessingServiceInterface::class, $service);

        // Act
        $job = new ProcessLiveSessionCombatLogBuffer(PHP_INT_MAX);
        app()->call([$job, 'handle']);

        // Assert — expectations above
    }

    #[Test]
    public function handle_givenBatchesHeldBackBehindAGap_releasesTheJobUntilOneSecondAfterTheGracePeriodLapses(): void
    {
        // Arrange
        $this->travelTo(Carbon::parse('2026-06-11 22:00:00'));

        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();

        $service = $this->createMockPublic(LiveSessionBufferProcessingServiceInterface::class);
        $service->expects($this->once())->method('processBuffer')->willReturn(Carbon::now()->addSeconds(20));
        app()->instance(LiveSessionBufferProcessingServiceInterface::class, $service);

        $job = new ProcessLiveSessionCombatLogBuffer($liveSession->id)->withFakeQueueInteractions();

        try {
            // Act
            app()->call([$job, 'handle']);

            // Assert
            $job->assertReleased(21);
        } finally {
            $liveSession->delete();
            $liveSession->dungeonRoute?->delete();
        }
    }

    #[Test]
    public function handle_givenNothingHeldBack_doesNotReleaseTheJob(): void
    {
        // Arrange
        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();

        $service = $this->createMockPublic(LiveSessionBufferProcessingServiceInterface::class);
        $service->expects($this->once())->method('processBuffer')->willReturn(null);
        app()->instance(LiveSessionBufferProcessingServiceInterface::class, $service);

        $job = new ProcessLiveSessionCombatLogBuffer($liveSession->id)->withFakeQueueInteractions();

        try {
            // Act
            app()->call([$job, 'handle']);

            // Assert
            $job->assertNotReleased();
        } finally {
            $liveSession->delete();
            $liveSession->dungeonRoute?->delete();
        }
    }

    #[Test]
    public function middleware_givenAJob_expiresTheOverlapLockAfterTheJobTimeout(): void
    {
        // Arrange
        $job = new ProcessLiveSessionCombatLogBuffer(1);

        // Act
        $middleware = $job->middleware()[0];

        // Assert - a worker killed mid-job must not hold the lock forever
        $this->assertGreaterThan($job->timeout, $middleware->expiresAfter);
    }

    #[Test]
    public function middleware_givenAnOverlappingJob_releasesItInsteadOfDroppingIt(): void
    {
        // Arrange
        $job = new ProcessLiveSessionCombatLogBuffer(1);

        // Act
        $middleware = $job->middleware()[0];

        // Assert
        $this->assertSame(ProcessLiveSessionCombatLogBuffer::RELEASE_AFTER_SECONDS, $middleware->releaseAfter);
    }

    #[Test]
    public function retryUntil_givenAJob_outlastsTheOverlapLockExpiry(): void
    {
        // Arrange
        $job = new ProcessLiveSessionCombatLogBuffer(1);

        // Act
        $retryUntil = $job->retryUntil();
        $lockExpiry = $job->middleware()[0]->expiresAfter;

        // Assert - a job released behind a dead worker's lock must still be retried once that lock expires
        $this->assertGreaterThan(Carbon::now()->addSeconds($lockExpiry)->getTimestamp(), $retryUntil->getTimestamp());
    }

    // -------------------------------------------------------------------------
    // Service: pending batches and the checkpoint
    // -------------------------------------------------------------------------

    #[Test]
    public function processBuffer_givenPendingBatches_consumesThemAndStoresTheCheckpoint(): void
    {
        // Arrange
        $lines = $this->getPitOfSaronLines(0, 500);

        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();
        $first       = $this->storeBatch($liveSession, array_slice($lines, 0, 250), 1);
        $second      = $this->storeBatch($liveSession, array_slice($lines, 250), 2);

        try {
            // Act
            app()->make(LiveSessionBufferProcessingServiceInterface::class)->processBuffer($liveSession);

            // Assert
            foreach ([$first, $second] as $batch) {
                $batch->refresh();
                $this->assertNotNull($batch->consumed_at, 'A replayed batch must be consumed');
                $this->assertNull($batch->lines, 'A consumed batch must release its lines');
            }

            $checkpoint = LiveSessionCombatLogBuffer::query()->where('live_session_id', $liveSession->id)->first();
            $this->assertNotNull($checkpoint, 'The run must store a checkpoint');
            $this->assertNotEmpty(gzdecode($checkpoint->buffer));
        } finally {
            $this->cleanupLiveSessionEnemyState($liveSession->id, []);
            LiveSessionPlayerPosition::query()->where('live_session_id', $liveSession->id)->delete();
            $this->cleanupCombatLog($liveSession->id);
            $liveSession->delete();
            $liveSession->dungeonRoute?->delete();
        }
    }

    #[Test]
    public function processBuffer_givenABatchStoredWhileRunning_leavesItPendingForTheNextRun(): void
    {
        // Arrange - a batch arrives right after the run read the pending batches
        $lines = $this->getPitOfSaronLines(0, 500);

        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();
        $readBatch   = $this->storeBatch($liveSession, array_slice($lines, 0, 250), 1);
        $lateLines   = array_slice($lines, 250);

        $batchRepository = new class($this, $lateLines) extends LiveSessionCombatLogBatchRepository {
            public ?LiveSessionCombatLogBatch $lateBatch = null;

            /**
             * @param array<int, string> $lateLines
             */
            public function __construct(private readonly ProcessLiveSessionCombatLogBufferTest $test, private readonly array $lateLines)
            {
                parent::__construct();
            }

            public function getPendingBatches(LiveSession $liveSession): Collection
            {
                $pendingBatches  = parent::getPendingBatches($liveSession);
                $this->lateBatch = $this->test->storeBatch($liveSession, $this->lateLines, 2);

                return $pendingBatches;
            }
        };

        try {
            // Act
            app()->make(LiveSessionBufferProcessingService::class, ['liveSessionCombatLogBatchRepository' => $batchRepository])
                ->processBuffer($liveSession);
            $lateBatchAfterFirstRun = $batchRepository->lateBatch->fresh();

            app()->make(LiveSessionBufferProcessingServiceInterface::class)->processBuffer($liveSession);

            // Assert
            $this->assertNotNull($readBatch->fresh()->consumed_at, 'The batch the run read must be consumed');
            $this->assertNull($lateBatchAfterFirstRun->consumed_at, 'A batch stored after the read must stay pending');
            $this->assertNotNull($lateBatchAfterFirstRun->lines, 'A batch stored after the read must keep its lines');
            $this->assertNotNull($batchRepository->lateBatch->fresh()->consumed_at, 'The next run must consume the late batch');
        } finally {
            $this->cleanupLiveSessionEnemyState($liveSession->id, []);
            LiveSessionPlayerPosition::query()->where('live_session_id', $liveSession->id)->delete();
            $this->cleanupCombatLog($liveSession->id);
            $liveSession->delete();
            $liveSession->dungeonRoute?->delete();
        }
    }

    #[Test]
    public function processBuffer_givenBatchesStoredOutOfOrder_replaysThemBySequence(): void
    {
        // Arrange - two COMBATANT_INFO lines, both kept in the checkpoint, stored with the later sequence first
        $combatantInfoLines = array_values(array_filter(
            $this->getPitOfSaronLines(0, 100),
            static fn(string $line): bool => str_contains($line, 'COMBATANT_INFO'),
        ));
        $this->assertGreaterThanOrEqual(2, count($combatantInfoLines));
        [$firstLine, $secondLine] = $combatantInfoLines;

        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();
        $this->storeBatch($liveSession, [$secondLine], 2);
        $this->storeBatch($liveSession, [$firstLine], 1);

        try {
            // Act
            app()->make(LiveSessionBufferProcessingServiceInterface::class)->processBuffer($liveSession);

            // Assert
            $checkpoint = LiveSessionCombatLogBuffer::query()->where('live_session_id', $liveSession->id)->first();
            $this->assertNotNull($checkpoint, 'The run must store a checkpoint');
            $this->assertSame([$firstLine, $secondLine], explode("\n", gzdecode($checkpoint->buffer)));
        } finally {
            $this->cleanupLiveSessionEnemyState($liveSession->id, []);
            LiveSessionPlayerPosition::query()->where('live_session_id', $liveSession->id)->delete();
            $this->cleanupCombatLog($liveSession->id);
            $liveSession->delete();
            $liveSession->dungeonRoute?->delete();
        }
    }

    // -------------------------------------------------------------------------
    // Service: sequence gaps
    // -------------------------------------------------------------------------

    #[Test]
    public function processBuffer_givenTheBatchWithTheKillsProcessedBeforeTheBatchWithTheSightings_recordsTheKills(): void
    {
        if (!file_exists(base_path('tests') . self::PIT_OF_SARON_EVENTS_FILE)) {
            $this->markTestSkipped('Pit of Saron events file not found');
        }

        // Arrange
        Event::fake([LiveSessionStateChangedEvent::class]);

        $lines    = $this->getPitOfSaronLines();
        $midpoint = intdiv(count($lines), 2);

        [$fullRoute, $fullSession, $fullKillZone]       = $this->arrangePitOfSaronSession();
        [$gappedRoute, $gappedSession, $gappedKillZone] = $this->arrangePitOfSaronSession();
        $this->storeBatch($fullSession, $lines, 1);

        try {
            /** @var LiveSessionBufferProcessingServiceInterface $service */
            $service = app()->make(LiveSessionBufferProcessingServiceInterface::class);
            $service->processBuffer($fullSession);

            // Act - the second half arrives and is processed before the first half exists
            $this->storeBatch($gappedSession, array_slice($lines, $midpoint), 2);
            $service->processBuffer($gappedSession);
            $this->storeBatch($gappedSession, array_slice($lines, 0, $midpoint), 1);
            $service->processBuffer($gappedSession);

            // Assert
            $fullKillCount = $fullSession->enemyStates()->where('state', LiveSessionEnemyStateType::Killed)->count();
            $this->assertGreaterThan(0, $fullKillCount);
            $this->assertSame(
                $fullKillCount,
                $gappedSession->enemyStates()->where('state', LiveSessionEnemyStateType::Killed)->count(),
                'A kill whose sighting arrives in a later batch must still be recorded',
            );
        } finally {
            $this->cleanupPitOfSaronSession($fullRoute, $fullSession, $fullKillZone);
            $this->cleanupPitOfSaronSession($gappedRoute, $gappedSession, $gappedKillZone);
        }
    }

    #[Test]
    public function processBuffer_givenABatchBehindAGap_consumesTheBatchesBeforeTheGapAndHoldsItBack(): void
    {
        // Arrange
        $this->travelTo(Carbon::parse('2026-06-11 22:00:00'));
        [$firstLine, $secondLine, $heldLine] = $this->getPitOfSaronCombatantInfoLines(3);

        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();
        $first       = $this->storeBatch($liveSession, [$firstLine], 1);
        $second      = $this->storeBatch($liveSession, [$secondLine], 2);
        $held        = $this->storeBatch($liveSession, [$heldLine], 4);

        try {
            // Act
            $heldUntil = app()->make(LiveSessionBufferProcessingServiceInterface::class)->processBuffer($liveSession);

            // Assert
            $this->assertNotNull($first->fresh()->consumed_at, 'The batches before the gap must be consumed');
            $this->assertNotNull($second->fresh()->consumed_at, 'The batches before the gap must be consumed');
            $held->refresh();
            $this->assertNull($held->consumed_at, 'The batch behind the gap must stay pending');
            $this->assertSame([$heldLine], explode("\n", gzdecode($held->lines)), 'The batch behind the gap must keep its lines');
            $this->assertEquals(Carbon::now()->addSeconds(30), $heldUntil, 'The pass must say when the gap is skipped');

            $checkpoint = LiveSessionCombatLogBuffer::query()->where('live_session_id', $liveSession->id)->firstOrFail();
            $this->assertSame([$firstLine, $secondLine], explode("\n", gzdecode($checkpoint->buffer)));
        } finally {
            $this->cleanupLiveSessionEnemyState($liveSession->id, []);
            LiveSessionPlayerPosition::query()->where('live_session_id', $liveSession->id)->delete();
            $this->cleanupCombatLog($liveSession->id);
            $liveSession->delete();
            $liveSession->dungeonRoute?->delete();
        }
    }

    #[Test]
    public function processBuffer_givenAGapOlderThanTheGracePeriod_skipsItAndConsumesTheHeldBatch(): void
    {
        // Arrange
        $this->travelTo(Carbon::parse('2026-06-11 22:00:00'));
        [$firstLine, $heldLine] = $this->getPitOfSaronCombatantInfoLines(2);

        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();
        $this->storeBatch($liveSession, [$firstLine], 1);
        $held = $this->storeBatch($liveSession, [$heldLine], 3);

        try {
            /** @var LiveSessionBufferProcessingServiceInterface $service */
            $service = app()->make(LiveSessionBufferProcessingServiceInterface::class);

            // Act
            $this->travel(29)->seconds();
            $heldUntilWithinGrace = $service->processBuffer($liveSession);
            $consumedWithinGrace  = $held->fresh()->consumed_at;
            $this->travel(2)->seconds();
            $heldUntilAfterGrace = $service->processBuffer($liveSession);

            // Assert
            $this->assertNotNull($heldUntilWithinGrace);
            $this->assertNull($consumedWithinGrace, 'The gap must hold the batch within the grace period');
            $this->assertNotNull($held->fresh()->consumed_at, 'The gap must be skipped once the grace period lapsed');
            $this->assertNull($heldUntilAfterGrace);

            $checkpoint = LiveSessionCombatLogBuffer::query()->where('live_session_id', $liveSession->id)->firstOrFail();
            $this->assertSame([$firstLine, $heldLine], explode("\n", gzdecode($checkpoint->buffer)));
        } finally {
            $this->cleanupLiveSessionEnemyState($liveSession->id, []);
            LiveSessionPlayerPosition::query()->where('live_session_id', $liveSession->id)->delete();
            $this->cleanupCombatLog($liveSession->id);
            $liveSession->delete();
            $liveSession->dungeonRoute?->delete();
        }
    }

    #[Test]
    #[DataProvider('firstSequenceProvider')]
    public function processBuffer_givenNothingConsumedYetAndTheLowestPendingSequenceZeroOrOne_consumesFromThere(int $firstSequence): void
    {
        // Arrange
        [$firstLine, $secondLine] = $this->getPitOfSaronCombatantInfoLines(2);

        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();
        $first       = $this->storeBatch($liveSession, [$firstLine], $firstSequence);
        $second      = $this->storeBatch($liveSession, [$secondLine], $firstSequence + 1);

        try {
            // Act
            $heldUntil = app()->make(LiveSessionBufferProcessingServiceInterface::class)->processBuffer($liveSession);

            // Assert
            $this->assertNotNull($first->fresh()->consumed_at);
            $this->assertNotNull($second->fresh()->consumed_at);
            $this->assertNull($heldUntil);
        } finally {
            $this->cleanupLiveSessionEnemyState($liveSession->id, []);
            LiveSessionPlayerPosition::query()->where('live_session_id', $liveSession->id)->delete();
            $this->cleanupCombatLog($liveSession->id);
            $liveSession->delete();
            $liveSession->dungeonRoute?->delete();
        }
    }

    /**
     * @return array<string, array{int}>
     */
    public static function firstSequenceProvider(): array
    {
        return [
            'from zero' => [0],
            'from one'  => [1],
        ];
    }

    #[Test]
    public function processBuffer_givenNothingConsumedYetAndTheLowestPendingSequenceAboveOne_holdsItBack(): void
    {
        // Arrange
        [$firstLine] = $this->getPitOfSaronCombatantInfoLines(1);

        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();
        $held        = $this->storeBatch($liveSession, [$firstLine], 5);

        try {
            // Act
            $heldUntil = app()->make(LiveSessionBufferProcessingServiceInterface::class)->processBuffer($liveSession);

            // Assert
            $this->assertNull($held->fresh()->consumed_at, 'Sequence 5 as the lowest pending one leaves 1 to 4 missing');
            $this->assertNotNull($heldUntil);
        } finally {
            $this->cleanupCombatLog($liveSession->id);
            $liveSession->delete();
            $liveSession->dungeonRoute?->delete();
        }
    }

    #[Test]
    public function processBuffer_givenOnlyBatchesHeldBack_parsesNothingAndLeavesTheCheckpointAlone(): void
    {
        // Arrange - the checkpoint covers sequence 1; sequence 3 waits for 2
        Event::fake([LiveSessionStateChangedEvent::class]);
        [$liveSession, $checkpoint] = $this->arrangeCheckpointWithoutPendingBatches();
        $held                       = $this->storeBatch($liveSession, ['6/11/2026 22:22:55.5142  CHALLENGE_MODE_START,"Pit of Saron",658,556,10,[162,9,10]'], 3);

        $combatLogService = $this->createMockPublic(CombatLogServiceInterface::class);
        $combatLogService->expects($this->never())->method('parseCombatLogStreaming');
        app()->instance(CombatLogServiceInterface::class, $combatLogService);

        try {
            // Act
            $heldUntil = app()->make(LiveSessionBufferProcessingServiceInterface::class)->processBuffer($liveSession);

            // Assert
            $this->assertNotNull($heldUntil);
            $this->assertNull($held->fresh()->consumed_at);
            $this->assertSame($checkpoint->buffer, $checkpoint->fresh()->buffer);
            Event::assertNotDispatched(LiveSessionStateChangedEvent::class);
        } finally {
            $this->cleanupCombatLog($liveSession->id);
            $liveSession->delete();
            $liveSession->dungeonRoute?->delete();
        }
    }

    #[Test]
    public function processBuffer_givenUnsequencedBatchesBesideABatchHeldBack_consumesTheUnsequencedOnes(): void
    {
        // Arrange
        [$firstLine, $secondLine, $heldLine] = $this->getPitOfSaronCombatantInfoLines(3);

        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();
        $held        = $this->storeBatch($liveSession, [$heldLine], 5);
        $first       = $this->storeBatch($liveSession, [$firstLine]);
        $second      = $this->storeBatch($liveSession, [$secondLine]);

        try {
            // Act
            app()->make(LiveSessionBufferProcessingServiceInterface::class)->processBuffer($liveSession);

            // Assert
            $this->assertNotNull($first->fresh()->consumed_at, 'An unsequenced batch has no gap to wait for');
            $this->assertNotNull($second->fresh()->consumed_at, 'An unsequenced batch has no gap to wait for');
            $this->assertNull($held->fresh()->consumed_at);

            $checkpoint = LiveSessionCombatLogBuffer::query()->where('live_session_id', $liveSession->id)->firstOrFail();
            $this->assertSame([$firstLine, $secondLine], explode("\n", gzdecode($checkpoint->buffer)), 'Unsequenced batches replay by arrival');
        } finally {
            $this->cleanupLiveSessionEnemyState($liveSession->id, []);
            LiveSessionPlayerPosition::query()->where('live_session_id', $liveSession->id)->delete();
            $this->cleanupCombatLog($liveSession->id);
            $liveSession->delete();
            $liveSession->dungeonRoute?->delete();
        }
    }

    #[Test]
    public function processBuffer_givenABatchBelowTheHighestConsumedSequence_consumesIt(): void
    {
        // Arrange - sequence 2 arrives after its gap was skipped and 3 consumed
        [$lateLine] = $this->getPitOfSaronCombatantInfoLines(1);

        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();
        $this->storeBatch($liveSession, ['6/11/2026 22:21:11.5382  ZONE_CHANGE,658,"Pit of Saron",23'], 3)
            ->update(['lines' => null, 'consumed_at' => Carbon::now()]);
        $late = $this->storeBatch($liveSession, [$lateLine], 2);

        try {
            // Act
            $heldUntil = app()->make(LiveSessionBufferProcessingServiceInterface::class)->processBuffer($liveSession);

            // Assert
            $this->assertNotNull($late->fresh()->consumed_at);
            $this->assertNull($heldUntil);
        } finally {
            $this->cleanupLiveSessionEnemyState($liveSession->id, []);
            LiveSessionPlayerPosition::query()->where('live_session_id', $liveSession->id)->delete();
            $this->cleanupCombatLog($liveSession->id);
            $liveSession->delete();
            $liveSession->dungeonRoute?->delete();
        }
    }

    #[Test]
    public function processBuffer_givenSpamHeavyBatch_keepsOnlyTheRelevantLinesInTheCheckpoint(): void
    {
        if (!file_exists(base_path('tests') . self::PIT_OF_SARON_EVENTS_FILE)) {
            $this->markTestSkipped('Pit of Saron events file not found');
        }

        // Arrange
        Event::fake([LiveSessionStateChangedEvent::class]);

        $dungeon        = Dungeon::where('challenge_mode_id', self::PIT_OF_SARON_CHALLENGE_MODE_ID)->firstOrFail();
        $mappingVersion = $dungeon->getCurrentMappingVersion();

        if ($mappingVersion === null) {
            $this->markTestSkipped('No current mapping version for Pit of Saron');
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

        // The fixture is already a curated "relevant events" log; inject realistic spam (repeated damage
        // to an already-sighted enemy) so there is something for the reduction to actually drop.
        $relevantLines = $this->getPitOfSaronLines();
        $spamTemplate  = collect($relevantLines)->first(static fn(string $line): bool => str_contains($line, 'SPELL_DAMAGE'));
        $this->assertNotNull($spamTemplate);

        $spamLog = array_merge($relevantLines, array_fill(0, 5000, $spamTemplate));
        $this->storeBatch($liveSession, $spamLog, 1);

        try {
            $liveSession->load(['dungeonRoute.mappingVersion.dungeon.floors']);

            // Act
            app()->make(LiveSessionBufferProcessingServiceInterface::class)->processBuffer($liveSession);

            // Assert: the 5000 spam lines are gone, leaving roughly the original relevant set
            $checkpoint = LiveSessionCombatLogBuffer::query()->where('live_session_id', $liveSession->id)->first();
            $this->assertNotNull($checkpoint, 'The run must store a checkpoint');
            $keptLines = explode("\n", gzdecode($checkpoint->buffer));

            $this->assertGreaterThan(0, count($keptLines));
            $this->assertLessThan(count($spamLog) / 2, count($keptLines), 'Expected the reduction to drop the injected spam');
            $this->assertLessThan(count($relevantLines) + 100, count($keptLines), 'Expected the checkpoint to be close to the relevant set');
        } finally {
            $this->cleanupLiveSessionEnemyState($liveSession->id, []);
            LiveSessionPlayerPosition::query()->where('live_session_id', $liveSession->id)->delete();
            $this->cleanupCombatLog($liveSession->id);
            $liveSession->delete();
            $dungeonRoute->delete();
        }
    }

    // -------------------------------------------------------------------------
    // Service: player position owner
    // -------------------------------------------------------------------------

    #[Test]
    public function resolvePlayerPositionOwner_givenInfoGuidIsTheDest_attributesThePositionToTheDest(): void
    {
        // Arrange
        $event = $this->makeAdvancedEvent(
            sourceGuid: 'Player-1403-0A000001',
            sourceName: 'Healzor',
            destGuid: 'Player-1403-0A000002',
            destName: 'Tankadin',
            infoGuid: 'Player-1403-0A000002',
        );

        // Act
        $result = $this->invokeResolvePlayerPositionOwner($event);

        // Assert
        $this->assertSame(['guid' => 'Player-1403-0A000002', 'characterName' => 'Tankadin'], $result);
    }

    #[Test]
    public function resolvePlayerPositionOwner_givenInfoGuidIsTheSource_attributesThePositionToTheSource(): void
    {
        // Arrange
        $event = $this->makeAdvancedEvent(
            sourceGuid: 'Player-1403-0A000001',
            sourceName: 'Healzor',
            destGuid: 'Creature-0-3767-658-26019-252558-00006B5B0F',
            destName: 'Enemy',
            infoGuid: 'Player-1403-0A000001',
        );

        // Act
        $result = $this->invokeResolvePlayerPositionOwner($event);

        // Assert
        $this->assertSame(['guid' => 'Player-1403-0A000001', 'characterName' => 'Healzor'], $result);
    }

    #[Test]
    public function resolvePlayerPositionOwner_givenInfoGuidIsACreature_returnsNull(): void
    {
        // Arrange - a player hitting an enemy whose own position is in the advanced data
        $event = $this->makeAdvancedEvent(
            sourceGuid: 'Player-1403-0A000001',
            sourceName: 'Healzor',
            destGuid: 'Creature-0-3767-658-26019-252558-00006B5B0F',
            destName: 'Enemy',
            infoGuid: 'Creature-0-3767-658-26019-252558-00006B5B0F',
        );

        // Act
        $result = $this->invokeResolvePlayerPositionOwner($event);

        // Assert
        $this->assertNull($result);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Create a KillZone for $dungeonRoute containing all enemies for the NPC IDs
     * that appear as kills in the Pit of Saron events file. Caller must clean up via
     * cleanupLiveSessionEnemyState().
     */
    private function createPitOfSaronKillZone(DungeonRoute $dungeonRoute): KillZone
    {
        $killZone = KillZone::factory()->create([
            'dungeon_route_id' => $dungeonRoute->id,
            'index'            => 1,
        ]);

        $enemies = Enemy::whereIn('npc_id', self::PIT_OF_SARON_KILLED_NPC_IDS)
            ->where('mapping_version_id', $dungeonRoute->mapping_version_id)
            ->get();

        foreach ($enemies as $enemy) {
            KillZoneEnemy::query()->create([
                'kill_zone_id' => $killZone->id,
                'npc_id'       => $enemy->npc_id,
                'mdt_id'       => $enemy->mdt_id,
                'enemy_id'     => $enemy->id,
            ]);
        }

        return $killZone;
    }

    /**
     * @return array{DungeonRoute, LiveSession, KillZone}
     */
    private function arrangePitOfSaronSession(): array
    {
        $dungeon = Dungeon::where('challenge_mode_id', self::PIT_OF_SARON_CHALLENGE_MODE_ID)->firstOrFail();

        /** @var DungeonRoute $dungeonRoute */
        $dungeonRoute = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $dungeon->getCurrentMappingVersion()->id,
        ]);

        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create([
            'dungeon_route_id' => $dungeonRoute->id,
        ]);
        $liveSession->load(['dungeonRoute.mappingVersion.dungeon.floors']);

        return [$dungeonRoute, $liveSession, $this->createPitOfSaronKillZone($dungeonRoute)];
    }

    /**
     * @return array{DungeonRoute, LiveSession, KillZone}
     */
    private function arrangePitOfSaronSessionWithTheFullLog(): array
    {
        [$dungeonRoute, $liveSession, $killZone] = $this->arrangePitOfSaronSession();
        $this->storeBatch($liveSession, $this->getPitOfSaronLines(), 1);

        return [$dungeonRoute, $liveSession, $killZone];
    }

    private function cleanupPitOfSaronSession(DungeonRoute $dungeonRoute, LiveSession $liveSession, KillZone $killZone): void
    {
        $this->cleanupLiveSessionEnemyState($liveSession->id, [$killZone->id]);
        LiveSessionPlayerPosition::query()->where('live_session_id', $liveSession->id)->delete();
        $this->cleanupCombatLog($liveSession->id);
        $liveSession->delete();
        $dungeonRoute->delete();
    }

    /**
     * @param int|int[] $liveSessionIds
     * @param int[]     $killZoneIds
     */
    private function cleanupLiveSessionEnemyState(int|array $liveSessionIds, array $killZoneIds): void
    {
        $liveSessionIds = (array)$liveSessionIds;

        LiveSessionEnemyState::query()->whereIn('live_session_id', $liveSessionIds)->delete();
        LiveSessionOverpulledEnemy::query()->whereIn('live_session_id', $liveSessionIds)->delete();
        OverpulledEnemy::query()->whereIn('live_session_id', $liveSessionIds)->delete();
        KillZoneEnemy::query()->whereIn('kill_zone_id', $killZoneIds)->delete();

        foreach ($killZoneIds as $killZoneId) {
            KillZone::query()->where('id', $killZoneId)->delete();
        }
    }

    /**
     * Public so the repository double in {@see self::processBuffer_givenABatchStoredWhileRunning_leavesItPendingForTheNextRun()}
     * can store a batch mid-run.
     *
     * @param array<int, string> $lines
     */
    public function storeBatch(LiveSession $liveSession, array $lines, ?int $sequence = null): LiveSessionCombatLogBatch
    {
        return LiveSessionCombatLogBatch::factory()->withLines($lines)->create([
            'live_session_id' => $liveSession->id,
            'sequence'        => $sequence,
        ]);
    }

    /**
     * @param int|int[] $liveSessionIds
     */
    private function cleanupCombatLog(int|array $liveSessionIds): void
    {
        $liveSessionIds = (array)$liveSessionIds;

        LiveSessionCombatLogBatch::query()->whereIn('live_session_id', $liveSessionIds)->delete();
        LiveSessionCombatLogBuffer::query()->whereIn('live_session_id', $liveSessionIds)->delete();
    }

    /**
     * @return array<int, string>
     */
    private function getPitOfSaronLines(int $offset = 0, ?int $length = null): array
    {
        return array_slice(file(base_path('tests') . self::PIT_OF_SARON_EVENTS_FILE, FILE_IGNORE_NEW_LINES), $offset, $length);
    }

    /**
     * COMBATANT_INFO lines are each kept in the checkpoint, so its content shows which batches a pass consumed.
     *
     * @return array<int, string>
     */
    private function getPitOfSaronCombatantInfoLines(int $count): array
    {
        $combatantInfoLines = array_values(array_filter(
            $this->getPitOfSaronLines(0, 100),
            static fn(string $line): bool => str_contains($line, 'COMBATANT_INFO'),
        ));
        $this->assertGreaterThanOrEqual($count, count($combatantInfoLines));

        return array_slice($combatantInfoLines, 0, $count);
    }

    /**
     * Run a pass whose checkpoint write throws, as a worker dying between the state writes and the commit would.
     */
    private function processBufferCrashingBeforeTheCheckpointCommits(LiveSession $liveSession): void
    {
        $crashingBufferRepository = new class extends LiveSessionCombatLogBufferRepository {
            public function saveCheckpoint(LiveSession $liveSession, string $compressedBuffer): void
            {
                throw new RuntimeException('Worker died before the checkpoint committed');
            }
        };

        try {
            app()->make(LiveSessionBufferProcessingService::class, ['liveSessionCombatLogBufferRepository' => $crashingBufferRepository])
                ->processBuffer($liveSession);
        } catch (RuntimeException) {
            return;
        }

        $this->fail('The pass was expected to crash before its checkpoint committed');
    }

    /**
     * @return array{LiveSession, LiveSessionCombatLogBuffer}
     */
    private function arrangeCheckpointWithoutPendingBatches(): array
    {
        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();

        $checkpoint = LiveSessionCombatLogBuffer::factory()->create([
            'live_session_id' => $liveSession->id,
            'buffer'          => gzencode(implode("\n", $this->getPitOfSaronLines(0, 100)), 6),
        ]);

        $this->storeBatch($liveSession, ['6/11/2026 22:21:11.5382  ZONE_CHANGE,658,"Pit of Saron",23'], 1)
            ->update(['lines' => null, 'consumed_at' => Carbon::now()]);

        return [$liveSession, $checkpoint];
    }

    /**
     * @return array{guid: string, characterName: string}|null
     */
    private function invokeResolvePlayerPositionOwner(AdvancedCombatLogEvent $event): ?array
    {
        return new ReflectionMethod(LiveSessionBufferProcessingService::class, 'resolvePlayerPositionOwner')->invoke(null, $event);
    }

    private function makeAdvancedEvent(
        string $sourceGuid,
        string $sourceName,
        string $destGuid,
        string $destName,
        string $infoGuid,
    ): AdvancedCombatLogEvent {
        $genericData = $this->createMock(GenericDataInterface::class);
        $genericData->method('getSourceGuid')->willReturn(Guid::createFromGuidString($sourceGuid));
        $genericData->method('getSourceName')->willReturn($sourceName);
        $genericData->method('getDestGuid')->willReturn(Guid::createFromGuidString($destGuid));
        $genericData->method('getDestName')->willReturn($destName);

        $advancedData = $this->createMock(AdvancedDataInterface::class);
        $advancedData->method('getInfoGuid')->willReturn(Guid::createFromGuidString($infoGuid));

        $event = $this->createMock(AdvancedCombatLogEvent::class);
        $event->method('getGenericData')->willReturn($genericData);
        $event->method('getAdvancedData')->willReturn($advancedData);

        return $event;
    }
}
