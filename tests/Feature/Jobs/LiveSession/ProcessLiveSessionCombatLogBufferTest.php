<?php

namespace Tests\Feature\Jobs\LiveSession;

use App\Events\LiveSession\EnemyKilledEvent;
use App\Events\LiveSession\PlayerMovedEvent;
use App\Jobs\LiveSession\ProcessLiveSessionCombatLogBuffer;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\LiveSession\LiveSession;
use App\Models\LiveSession\LiveSessionCombatLogBuffer;
use App\Models\LiveSession\LiveSessionKilledEnemy;
use App\Models\LiveSession\LiveSessionPlayerPosition;
use App\Models\User;
use App\Service\LiveSession\LiveSessionBufferProcessingServiceInterface;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('LiveSession')]
#[Group('ProcessLiveSessionCombatLogBuffer')]
final class ProcessLiveSessionCombatLogBufferTest extends PublicTestCase
{
    private const string PIT_OF_SARON_EVENTS_FILE = '/CombatLogs/mn_s1/WoWCombatLog-061126_213150_10_pit-of-saron_events.txt';

    private const int PIT_OF_SARON_CHALLENGE_MODE_ID = 556;

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
                return (function () {
                    return $this->liveSessionId;
                })->call($job) === $liveSession->id;
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

            // Assert: some enemies should have been killed
            $killedCount = LiveSessionKilledEnemy::query()
                ->where('live_session_id', $liveSession->id)
                ->count();

            $this->assertGreaterThan(0, $killedCount, 'Expected at least one killed enemy to be persisted');
        } finally {
            LiveSessionKilledEnemy::query()->where('live_session_id', $liveSession->id)->delete();
            LiveSessionPlayerPosition::query()->where('live_session_id', $liveSession->id)->delete();
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
            LiveSessionKilledEnemy::query()->where('live_session_id', $liveSession->id)->delete();
            LiveSessionPlayerPosition::query()->where('live_session_id', $liveSession->id)->delete();
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
        $fullSession = LiveSession::factory()->create(['dungeon_route_id' => $fullRoute->id]);
        $fullBuffer  = LiveSessionCombatLogBuffer::factory()->create([
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
        $chunkedSession = LiveSession::factory()->create(['dungeon_route_id' => $chunkedRoute->id]);

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
            LiveSessionKilledEnemy::query()->whereIn('live_session_id', [$fullSession->id, $chunkedSession->id])->delete();
            LiveSessionPlayerPosition::query()->whereIn('live_session_id', [$fullSession->id, $chunkedSession->id])->delete();
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

        // Arrange
        Event::fake([EnemyKilledEvent::class]);

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
            Event::assertDispatched(EnemyKilledEvent::class);
        } finally {
            LiveSessionKilledEnemy::query()->where('live_session_id', $liveSession->id)->delete();
            LiveSessionPlayerPosition::query()->where('live_session_id', $liveSession->id)->delete();
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

        // Arrange
        Event::fake([PlayerMovedEvent::class]);

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
            LiveSessionKilledEnemy::query()->where('live_session_id', $liveSession->id)->delete();
            LiveSessionPlayerPosition::query()->where('live_session_id', $liveSession->id)->delete();
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

        // Arrange
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

            // First run to populate the DB
            $service->processBuffer($liveSession);
            $countAfterFirst = LiveSessionKilledEnemy::query()
                ->where('live_session_id', $liveSession->id)
                ->count();

            // Act — second run with Event::fake() to track only new dispatches
            Event::fake([EnemyKilledEvent::class]);
            $service->processBuffer($liveSession);

            // Assert — no new EnemyKilledEvent should be dispatched because all enemies already persisted
            Event::assertNotDispatched(EnemyKilledEvent::class);
            $this->assertGreaterThan(0, $countAfterFirst);
        } finally {
            LiveSessionKilledEnemy::query()->where('live_session_id', $liveSession->id)->delete();
            LiveSessionPlayerPosition::query()->where('live_session_id', $liveSession->id)->delete();
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
}
