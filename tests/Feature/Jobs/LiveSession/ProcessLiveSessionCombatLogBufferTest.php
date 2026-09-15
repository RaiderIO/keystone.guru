<?php

namespace Tests\Feature\Jobs\LiveSession;

use App\Events\LiveSession\InCombatEnemiesChangedEvent;
use App\Events\LiveSession\OverpulledEnemy\OverpulledEnemyChangedEvent;
use App\Events\LiveSession\RouteCorrectionEvent;
use App\Events\Models\LiveSession\EnemyKilledEvent;
use App\Events\Models\LiveSession\PlayerMovedEvent;
use App\Jobs\LiveSession\ProcessLiveSessionCombatLogBuffer;
use App\Logic\CombatLog\CombatEvents\Advanced\AdvancedDataInterface;
use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\CombatEvents\GenericData\GenericDataInterface;
use App\Logic\CombatLog\Guid\Guid;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\KillZone\KillZone;
use App\Models\KillZone\KillZoneEnemy;
use App\Models\LiveSession\LiveSession;
use App\Models\LiveSession\LiveSessionCombatLogBuffer;
use App\Models\LiveSession\LiveSessionInCombatEnemy;
use App\Models\LiveSession\LiveSessionKilledEnemy;
use App\Models\LiveSession\LiveSessionObsoleteEnemy;
use App\Models\LiveSession\LiveSessionOverpulledEnemy;
use App\Models\LiveSession\LiveSessionPlayerPosition;
use App\Models\User;
use App\Service\LiveSession\LiveSessionBufferProcessingService;
use App\Service\LiveSession\LiveSessionBufferProcessingServiceInterface;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
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
            LiveSessionCombatLogBuffer::query()->where('live_session_id', $liveSession->id)->delete();
            $liveSession->delete();
            $liveSession->dungeonRoute?->delete();
        }
    }

    // -------------------------------------------------------------------------
    // Service: processBuffer
    // -------------------------------------------------------------------------

    #[Test]
    public function processBuffer_givenEmptyBuffer_doesNotPersistAnyKills(): void
    {
        // Arrange
        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();

        // No buffer record at all
        try {
            /** @var LiveSessionBufferProcessingServiceInterface $service */
            $service = app()->make(LiveSessionBufferProcessingServiceInterface::class);

            // Act
            $liveSession->load('combatLogBuffer');
            $service->processBuffer($liveSession);

            // Assert
            $this->assertSame(0, LiveSessionKilledEnemy::query()
                ->where('live_session_id', $liveSession->id)->count());
        } finally {
            LiveSessionKilledEnemy::query()->where('live_session_id', $liveSession->id)->delete();
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
        Event::fake([PlayerMovedEvent::class, EnemyKilledEvent::class, OverpulledEnemyChangedEvent::class, RouteCorrectionEvent::class, InCombatEnemiesChangedEvent::class]);

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

        $rawLines = file_get_contents(base_path('tests') . self::PIT_OF_SARON_EVENTS_FILE);
        $buffer   = LiveSessionCombatLogBuffer::factory()->create([
            'live_session_id' => $liveSession->id,
            'buffer'          => gzencode($rawLines, 6),
            'last_sequence'   => 1,
        ]);

        try {
            /** @var LiveSessionBufferProcessingServiceInterface $service */
            $service = app()->make(LiveSessionBufferProcessingServiceInterface::class);
            $liveSession->load(['dungeonRoute.mappingVersion.dungeon.floors', 'combatLogBuffer']);

            // Act
            $service->processBuffer($liveSession);

            // Assert: some enemies should have been killed (on-route)
            $killedCount = LiveSessionKilledEnemy::query()
                ->where('live_session_id', $liveSession->id)
                ->count();

            $this->assertGreaterThan(0, $killedCount, 'Expected at least one killed enemy to be persisted');
        } finally {
            $this->cleanupLiveSessionEnemyState($liveSession->id, [$killZone->id]);
            LiveSessionPlayerPosition::query()->where('live_session_id', $liveSession->id)->delete();
            LiveSessionInCombatEnemy::query()->where('live_session_id', $liveSession->id)->delete();
            $buffer->delete();
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
        Event::fake([PlayerMovedEvent::class, EnemyKilledEvent::class, OverpulledEnemyChangedEvent::class, RouteCorrectionEvent::class, InCombatEnemiesChangedEvent::class]);

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

        $rawLines = file_get_contents(base_path('tests') . self::PIT_OF_SARON_EVENTS_FILE);
        $buffer   = LiveSessionCombatLogBuffer::factory()->create([
            'live_session_id' => $liveSession->id,
            'buffer'          => gzencode($rawLines, 6),
            'last_sequence'   => 1,
        ]);

        try {
            /** @var LiveSessionBufferProcessingServiceInterface $service */
            $service = app()->make(LiveSessionBufferProcessingServiceInterface::class);
            $liveSession->load(['dungeonRoute.mappingVersion.dungeon.floors', 'combatLogBuffer']);

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
            LiveSessionInCombatEnemy::query()->where('live_session_id', $liveSession->id)->delete();
            $buffer->delete();
            $liveSession->delete();
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function processBuffer_givenSameBufferProcessedTwice_doesNotDuplicateKills(): void
    {
        if (!file_exists(base_path('tests') . self::PIT_OF_SARON_EVENTS_FILE)) {
            $this->markTestSkipped('Pit of Saron events file not found');
        }

        // Arrange
        Event::fake([PlayerMovedEvent::class, EnemyKilledEvent::class, OverpulledEnemyChangedEvent::class, RouteCorrectionEvent::class, InCombatEnemiesChangedEvent::class]);

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

        $rawLines = file_get_contents(base_path('tests') . self::PIT_OF_SARON_EVENTS_FILE);
        $buffer   = LiveSessionCombatLogBuffer::factory()->create([
            'live_session_id' => $liveSession->id,
            'buffer'          => gzencode($rawLines, 6),
            'last_sequence'   => 1,
        ]);

        try {
            /** @var LiveSessionBufferProcessingServiceInterface $service */
            $service = app()->make(LiveSessionBufferProcessingServiceInterface::class);
            $liveSession->load(['dungeonRoute.mappingVersion.dungeon.floors', 'combatLogBuffer']);

            // Act — process twice
            $service->processBuffer($liveSession);
            $countAfterFirst = LiveSessionKilledEnemy::query()
                ->where('live_session_id', $liveSession->id)
                ->count();

            $service->processBuffer($liveSession);
            $countAfterSecond = LiveSessionKilledEnemy::query()
                ->where('live_session_id', $liveSession->id)
                ->count();

            // Assert: processing the same buffer twice does not create duplicates
            $this->assertGreaterThan(0, $countAfterFirst);
            $this->assertSame($countAfterFirst, $countAfterSecond);
        } finally {
            $this->cleanupLiveSessionEnemyState($liveSession->id, [$killZone->id]);
            LiveSessionPlayerPosition::query()->where('live_session_id', $liveSession->id)->delete();
            LiveSessionInCombatEnemy::query()->where('live_session_id', $liveSession->id)->delete();
            $buffer->delete();
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
        Event::fake([PlayerMovedEvent::class, EnemyKilledEvent::class, OverpulledEnemyChangedEvent::class, RouteCorrectionEvent::class, InCombatEnemiesChangedEvent::class]);

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
        $fullBuffer   = LiveSessionCombatLogBuffer::factory()->create([
            'live_session_id' => $fullSession->id,
            'buffer'          => gzencode(implode("\n", $allLines), 6),
            'last_sequence'   => 1,
        ]);

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
        $secondHalf = array_slice($allLines, 0); // full accumulated buffer after second batch

        $chunkedBuffer = LiveSessionCombatLogBuffer::factory()->create([
            'live_session_id' => $chunkedSession->id,
            'buffer'          => gzencode(implode("\n", $firstHalf), 6),
            'last_sequence'   => 1,
        ]);

        try {
            /** @var LiveSessionBufferProcessingServiceInterface $service */
            $service = app()->make(LiveSessionBufferProcessingServiceInterface::class);

            // Run full log
            $fullSession->load(['dungeonRoute.mappingVersion.dungeon.floors', 'combatLogBuffer']);
            $service->processBuffer($fullSession);
            $fullKillCount = LiveSessionKilledEnemy::query()
                ->where('live_session_id', $fullSession->id)
                ->count();

            // Run first chunk
            $chunkedSession->load(['dungeonRoute.mappingVersion.dungeon.floors', 'combatLogBuffer']);
            $service->processBuffer($chunkedSession);

            // Simulate second batch: update buffer to full accumulated log and re-run
            $chunkedBuffer->buffer        = gzencode(implode("\n", $secondHalf), 6);
            $chunkedBuffer->last_sequence = 2;
            $chunkedBuffer->save();

            $chunkedSession->load(['dungeonRoute.mappingVersion.dungeon.floors', 'combatLogBuffer']);
            $service->processBuffer($chunkedSession);

            $chunkedKillCount = LiveSessionKilledEnemy::query()
                ->where('live_session_id', $chunkedSession->id)
                ->count();

            // Assert: chunked processing arrives at the same kill count as one-shot processing
            $this->assertSame($fullKillCount, $chunkedKillCount);
        } finally {
            $sessionIds  = [$fullSession->id, $chunkedSession->id];
            $killZoneIds = [$fullKillZone->id, $chunkedKillZone->id];

            $this->cleanupLiveSessionEnemyState($sessionIds, $killZoneIds);
            LiveSessionPlayerPosition::query()->whereIn('live_session_id', $sessionIds)->delete();
            $fullBuffer->delete();
            $chunkedBuffer->delete();
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
        Event::fake([PlayerMovedEvent::class, EnemyKilledEvent::class, OverpulledEnemyChangedEvent::class, RouteCorrectionEvent::class, InCombatEnemiesChangedEvent::class]);

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
        $fullBuffer   = LiveSessionCombatLogBuffer::factory()->create([
            'live_session_id' => $fullSession->id,
            'buffer'          => gzencode(implode("\n", $allLines), 6),
            'last_sequence'   => 1,
        ]);

        /** @var DungeonRoute $chunkedRoute */
        $chunkedRoute = DungeonRoute::factory()->create(['dungeon_id' => $dungeon->id, 'mapping_version_id' => $mappingVersion->id]);
        /** @var LiveSession $chunkedSession */
        $chunkedSession  = LiveSession::factory()->create(['dungeon_route_id' => $chunkedRoute->id]);
        $chunkedKillZone = $this->createPitOfSaronKillZone($chunkedRoute);
        $chunkedBuffer   = LiveSessionCombatLogBuffer::factory()->create([
            'live_session_id' => $chunkedSession->id,
            'buffer'          => null,
            'last_sequence'   => null,
        ]);

        try {
            /** @var LiveSessionBufferProcessingServiceInterface $service */
            $service = app()->make(LiveSessionBufferProcessingServiceInterface::class);

            // One-shot run
            $fullSession->load(['dungeonRoute.mappingVersion.dungeon.floors', 'combatLogBuffer']);
            $service->processBuffer($fullSession);
            $fullKillCount = $fullSession->killedEnemies()->count();

            // Incremental run: feed the log in chunks, appending each new chunk to the *reduced* buffer
            // that the previous processBuffer() left behind — exactly how the live controller appends raw
            // batches. This is what proves a kill spanning chunks is not lost by the reduction.
            $chunks = array_chunk($allLines, (int)ceil(count($allLines) / 5));
            foreach ($chunks as $index => $chunk) {
                $chunkedBuffer->refresh();
                $existing = $chunkedBuffer->buffer !== null ? (gzdecode($chunkedBuffer->buffer) ?: '') : '';

                $combined                     = $existing === '' ? implode("\n", $chunk) : $existing . "\n" . implode("\n", $chunk);
                $chunkedBuffer->buffer        = gzencode($combined, 6);
                $chunkedBuffer->last_sequence = $index + 1;
                $chunkedBuffer->save();

                $chunkedSession->load(['dungeonRoute.mappingVersion.dungeon.floors', 'combatLogBuffer']);
                $service->processBuffer($chunkedSession);
            }

            $chunkedKillCount = $chunkedSession->killedEnemies()->count();

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
            $fullBuffer->delete();
            $chunkedBuffer->delete();
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
    public function processBuffer_givenRealPitOfSaronLog_dispatchesEnemyKilledEvent(): void
    {
        if (!file_exists(base_path('tests') . self::PIT_OF_SARON_EVENTS_FILE)) {
            $this->markTestSkipped('Pit of Saron events file not found');
        }

        // Arrange — fake all live-session broadcasts so neither needs a live Reverb/Pusher connection
        Event::fake([EnemyKilledEvent::class, PlayerMovedEvent::class, OverpulledEnemyChangedEvent::class, RouteCorrectionEvent::class, InCombatEnemiesChangedEvent::class]);

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

        $rawLines = file_get_contents(base_path('tests') . self::PIT_OF_SARON_EVENTS_FILE);
        $buffer   = LiveSessionCombatLogBuffer::factory()->create([
            'live_session_id' => $liveSession->id,
            'buffer'          => gzencode($rawLines, 6),
            'last_sequence'   => 1,
        ]);

        try {
            /** @var LiveSessionBufferProcessingServiceInterface $service */
            $service = app()->make(LiveSessionBufferProcessingServiceInterface::class);
            $liveSession->load(['dungeonRoute.mappingVersion.dungeon.floors', 'combatLogBuffer']);

            // Act
            $service->processBuffer($liveSession);

            // Assert
            Event::assertDispatched(EnemyKilledEvent::class);
        } finally {
            $this->cleanupLiveSessionEnemyState($liveSession->id, [$killZone->id]);
            LiveSessionPlayerPosition::query()->where('live_session_id', $liveSession->id)->delete();
            LiveSessionInCombatEnemy::query()->where('live_session_id', $liveSession->id)->delete();
            $buffer->delete();
            $liveSession->delete();
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function processBuffer_givenRealPitOfSaronLog_dispatchesPlayerMovedEvent(): void
    {
        if (!file_exists(base_path('tests') . self::PIT_OF_SARON_EVENTS_FILE)) {
            $this->markTestSkipped('Pit of Saron events file not found');
        }

        // Arrange — fake all live-session broadcasts so neither needs a live Reverb/Pusher connection
        Event::fake([PlayerMovedEvent::class, EnemyKilledEvent::class, OverpulledEnemyChangedEvent::class, RouteCorrectionEvent::class, InCombatEnemiesChangedEvent::class]);

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

        $rawLines = file_get_contents(base_path('tests') . self::PIT_OF_SARON_EVENTS_FILE);
        $buffer   = LiveSessionCombatLogBuffer::factory()->create([
            'live_session_id' => $liveSession->id,
            'buffer'          => gzencode($rawLines, 6),
            'last_sequence'   => 1,
        ]);

        try {
            /** @var LiveSessionBufferProcessingServiceInterface $service */
            $service = app()->make(LiveSessionBufferProcessingServiceInterface::class);
            $liveSession->load(['dungeonRoute.mappingVersion.dungeon.floors', 'combatLogBuffer']);

            // Act
            $service->processBuffer($liveSession);

            // Assert
            Event::assertDispatched(PlayerMovedEvent::class);
        } finally {
            LiveSessionPlayerPosition::query()->where('live_session_id', $liveSession->id)->delete();
            LiveSessionInCombatEnemy::query()->where('live_session_id', $liveSession->id)->delete();
            $buffer->delete();
            $liveSession->delete();
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function processBuffer_givenSameBufferProcessedTwice_doesNotDispatchDuplicateEnemyKilledEvents(): void
    {
        if (!file_exists(base_path('tests') . self::PIT_OF_SARON_EVENTS_FILE)) {
            $this->markTestSkipped('Pit of Saron events file not found');
        }

        // Arrange — fake all live-session broadcasts so the first run does not need a live Reverb/Pusher connection
        Event::fake([EnemyKilledEvent::class, PlayerMovedEvent::class, OverpulledEnemyChangedEvent::class, RouteCorrectionEvent::class, InCombatEnemiesChangedEvent::class]);

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

        $rawLines = file_get_contents(base_path('tests') . self::PIT_OF_SARON_EVENTS_FILE);
        $buffer   = LiveSessionCombatLogBuffer::factory()->create([
            'live_session_id' => $liveSession->id,
            'buffer'          => gzencode($rawLines, 6),
            'last_sequence'   => 1,
        ]);

        try {
            /** @var LiveSessionBufferProcessingServiceInterface $service */
            $service = app()->make(LiveSessionBufferProcessingServiceInterface::class);
            $liveSession->load(['dungeonRoute.mappingVersion.dungeon.floors', 'combatLogBuffer']);

            // First run to populate the DB
            $service->processBuffer($liveSession);
            $countAfterFirst = LiveSessionKilledEnemy::query()
                ->where('live_session_id', $liveSession->id)
                ->count();

            // Act — reset the fake before the second run so we only track new dispatches
            Event::fake([EnemyKilledEvent::class, PlayerMovedEvent::class, OverpulledEnemyChangedEvent::class, RouteCorrectionEvent::class, InCombatEnemiesChangedEvent::class]);
            $service->processBuffer($liveSession);

            // Assert — no new EnemyKilledEvent should be dispatched because all enemies already persisted
            Event::assertNotDispatched(EnemyKilledEvent::class);
            $this->assertGreaterThan(0, $countAfterFirst);
        } finally {
            $this->cleanupLiveSessionEnemyState($liveSession->id, [$killZone->id]);
            LiveSessionPlayerPosition::query()->where('live_session_id', $liveSession->id)->delete();
            LiveSessionInCombatEnemy::query()->where('live_session_id', $liveSession->id)->delete();
            $buffer->delete();
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
    public function middleware_givenAJob_expiresTheOverlapLockAfterTheJobTimeout(): void
    {
        // Arrange
        $job = new ProcessLiveSessionCombatLogBuffer(1);

        // Act
        $middleware = $job->middleware()[0];

        // Assert - a worker killed mid-job must not hold the lock forever
        $this->assertGreaterThan($job->timeout, $middleware->expiresAfter);
    }

    // -------------------------------------------------------------------------
    // Service: reduceBuffer
    // -------------------------------------------------------------------------

    #[Test]
    public function reduceBuffer_givenABatchAppendedSinceTheRead_leavesTheBufferAlone(): void
    {
        // Arrange
        [$liveSession, $buffer, $tmpFile] = $this->arrangeBufferForReduction(revision: 5);

        try {
            // Act - the job read revision 4, and an append has bumped it to 5 since
            $this->invokeReduceBuffer($liveSession, $tmpFile, 4);

            // Assert
            $this->assertSame($buffer->buffer, $buffer->fresh()->buffer);
        } finally {
            @unlink($tmpFile);
            $buffer->delete();
            $liveSession->delete();
            $liveSession->dungeonRoute?->delete();
        }
    }

    #[Test]
    public function reduceBuffer_givenNoAppendSinceTheRead_replacesTheBufferWithTheReducedLines(): void
    {
        // Arrange
        [$liveSession, $buffer, $tmpFile] = $this->arrangeBufferForReduction(revision: 5);

        try {
            // Act
            $this->invokeReduceBuffer($liveSession, $tmpFile, 5);

            // Assert
            $this->assertNotSame($buffer->buffer, $buffer->fresh()->buffer);
        } finally {
            @unlink($tmpFile);
            $buffer->delete();
            $liveSession->delete();
            $liveSession->dungeonRoute?->delete();
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
     * @param int|int[] $liveSessionIds
     * @param int[]     $killZoneIds
     */
    private function cleanupLiveSessionEnemyState(int|array $liveSessionIds, array $killZoneIds): void
    {
        $liveSessionIds = (array)$liveSessionIds;

        LiveSessionKilledEnemy::query()->whereIn('live_session_id', $liveSessionIds)->delete();
        LiveSessionOverpulledEnemy::query()->whereIn('live_session_id', $liveSessionIds)->delete();
        LiveSessionObsoleteEnemy::query()->whereIn('live_session_id', $liveSessionIds)->delete();
        LiveSessionInCombatEnemy::query()->whereIn('live_session_id', $liveSessionIds)->delete();
        KillZoneEnemy::query()->whereIn('kill_zone_id', $killZoneIds)->delete();

        foreach ($killZoneIds as $killZoneId) {
            KillZone::query()->where('id', $killZoneId)->delete();
        }
    }

    /**
     * @return array{LiveSession, LiveSessionCombatLogBuffer, string}
     */
    private function arrangeBufferForReduction(int $revision): array
    {
        $lines = array_slice(file(base_path('tests') . self::PIT_OF_SARON_EVENTS_FILE, FILE_IGNORE_NEW_LINES), 0, 500);

        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();

        $buffer = LiveSessionCombatLogBuffer::factory()->create([
            'live_session_id' => $liveSession->id,
            'buffer'          => gzencode(implode("\n", $lines), 6),
            'revision'        => $revision,
        ]);

        // The job reduces what it read into its temp file; keeping that shorter than the stored buffer makes
        // the guarded write observable no matter how much the reduction itself drops
        $tmpFile = tempnam(sys_get_temp_dir(), 'live_session_reduce_test_');
        file_put_contents($tmpFile, implode("\n", array_slice($lines, 0, 100)));

        return [$liveSession, $buffer, $tmpFile];
    }

    private function invokeReduceBuffer(LiveSession $liveSession, string $tmpFile, int $revisionAtRead): void
    {
        $service = app()->make(LiveSessionBufferProcessingService::class);

        new ReflectionMethod($service, 'reduceBuffer')->invoke($service, $liveSession, $tmpFile, collect(), $revisionAtRead);
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
