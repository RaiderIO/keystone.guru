<?php

namespace Tests\Feature\Console\Commands\Scheduler\LiveSession;

use App\Console\Commands\Scheduler\LiveSession\CleanupExpiredLiveSessions;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemies\OverpulledEnemy;
use App\Models\LiveSession\LiveSession;
use App\Models\LiveSession\LiveSessionCombatLogBatch;
use App\Models\LiveSession\LiveSessionCombatLogBuffer;
use App\Models\LiveSession\LiveSessionEnemyState;
use App\Models\LiveSession\LiveSessionEnemyStateType;
use App\Models\LiveSession\LiveSessionOverpulledEnemy;
use App\Models\LiveSession\LiveSessionPlayerPosition;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Console')]
#[Group('LiveSession')]
final class CleanupExpiredLiveSessionsTest extends PublicTestCase
{
    /** @var array<int> */
    private array $liveSessionIds = [];

    /** @var array<int> */
    private array $dungeonRouteIds = [];

    #[\Override]
    protected function tearDown(): void
    {
        try {
            if ($this->liveSessionIds !== []) {
                // Deleting each session through the model also deletes its child rows.
                LiveSession::query()->whereIn('id', $this->liveSessionIds)->get()->each(
                    static function (LiveSession $liveSession): void {
                        $liveSession->delete();
                    },
                );
            }

            if ($this->dungeonRouteIds !== []) {
                DungeonRoute::query()->whereIn('id', $this->dungeonRouteIds)->get()->each(
                    static function (DungeonRoute $dungeonRoute): void {
                        $dungeonRoute->delete();
                    },
                );
            }
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function handle_givenNoExpiredSessions_doesNotDeleteRelations(): void
    {
        // Arrange
        $activeSession = LiveSession::factory()->create([
            'expires_at' => now()->addHour(),
        ]);
        $this->liveSessionIds[]  = $activeSession->id;
        $this->dungeonRouteIds[] = $activeSession->dungeon_route_id;

        $killedEnemy = LiveSessionEnemyState::factory()->create([
            'live_session_id' => $activeSession->id,
        ]);

        // Act
        $this->artisan(CleanupExpiredLiveSessions::class)->assertSuccessful();

        // Assert
        $this->assertDatabaseHas('live_sessions', ['id' => $activeSession->id]);
        $this->assertDatabaseHas('live_session_enemy_states', ['id' => $killedEnemy->id]);
    }

    #[Test]
    public function handle_givenExpiredSession_deletesRelationsButKeepsSession(): void
    {
        // Arrange
        $expiredSession          = LiveSession::factory()->expired()->create();
        $this->liveSessionIds[]  = $expiredSession->id;
        $this->dungeonRouteIds[] = $expiredSession->dungeon_route_id;

        $overpulledEnemy = LiveSessionOverpulledEnemy::forceCreate([
            'live_session_id' => $expiredSession->id,
            'kill_zone_id'    => 999999,
            'npc_id'          => 12345,
            'mdt_id'          => 1,
        ]);

        $enemyStates = collect(LiveSessionEnemyStateType::cases())->map(
            static fn(LiveSessionEnemyStateType $state): LiveSessionEnemyState => LiveSessionEnemyState::factory()->inState($state)->create([
                'live_session_id' => $expiredSession->id,
            ]),
        );

        $playerPosition = LiveSessionPlayerPosition::factory()->create([
            'live_session_id' => $expiredSession->id,
        ]);

        $combatLogBuffer = LiveSessionCombatLogBuffer::factory()->create([
            'live_session_id' => $expiredSession->id,
        ]);

        $combatLogBatch = LiveSessionCombatLogBatch::factory()->create([
            'live_session_id' => $expiredSession->id,
        ]);

        // Act
        $this->artisan(CleanupExpiredLiveSessions::class)->assertSuccessful();

        // Assert — session row survives
        $this->assertDatabaseHas('live_sessions', ['id' => $expiredSession->id]);

        // Assert — all relation rows are gone
        $this->assertDatabaseMissing('live_session_overpulled_enemies', ['id' => $overpulledEnemy->id]);
        foreach ($enemyStates as $enemyState) {
            $this->assertDatabaseMissing('live_session_enemy_states', ['id' => $enemyState->id]);
        }
        $this->assertDatabaseMissing('live_session_player_positions', ['id' => $playerPosition->id]);
        $this->assertDatabaseMissing('live_session_combat_log_buffers', ['id' => $combatLogBuffer->id]);
        $this->assertDatabaseMissing('live_session_combat_log_batches', ['id' => $combatLogBatch->id]);
    }

    #[Test]
    public function handle_givenExpiredSessionWithLegacyOverpulledRows_deletesOnlyTheExpiredSessionsRows(): void
    {
        // Arrange
        $expiredSession          = LiveSession::factory()->expired()->create();
        $this->liveSessionIds[]  = $expiredSession->id;
        $this->dungeonRouteIds[] = $expiredSession->dungeon_route_id;

        $activeSession           = LiveSession::factory()->create(['expires_at' => now()->addHour()]);
        $this->liveSessionIds[]  = $activeSession->id;
        $this->dungeonRouteIds[] = $activeSession->dungeon_route_id;

        foreach ([$expiredSession, $activeSession] as $liveSession) {
            OverpulledEnemy::query()->insert([
                'live_session_id' => $liveSession->id,
                'kill_zone_id'    => 999999,
                'npc_id'          => 12345,
                'mdt_id'          => 1,
                'enemy_id'        => 0,
            ]);
        }

        // Act
        $this->artisan(CleanupExpiredLiveSessions::class)->assertSuccessful();

        // Assert
        $this->assertDatabaseMissing('overpulled_enemies', ['live_session_id' => $expiredSession->id]);
        $this->assertDatabaseHas('overpulled_enemies', ['live_session_id' => $activeSession->id]);
    }

    #[Test]
    public function handle_givenAnExpiredAndAnActiveSession_deletesOnlyTheExpiredSessionsRowsFromEveryTable(): void
    {
        // Arrange
        $expiredSession          = LiveSession::factory()->expired()->create();
        $this->liveSessionIds[]  = $expiredSession->id;
        $this->dungeonRouteIds[] = $expiredSession->dungeon_route_id;

        $activeSession           = LiveSession::factory()->create(['expires_at' => now()->addHour()]);
        $this->liveSessionIds[]  = $activeSession->id;
        $this->dungeonRouteIds[] = $activeSession->dungeon_route_id;

        foreach ([$expiredSession, $activeSession] as $liveSession) {
            $this->createOneRowInEveryChildTable($liveSession);
        }

        // Act
        $this->artisan(CleanupExpiredLiveSessions::class)->assertSuccessful();

        // Assert
        $this->assertSame(
            array_fill_keys($this->getChildTables(), 0),
            $this->countChildRows($expiredSession),
            'Every relation of the expired session must be deleted',
        );
        $this->assertSame(
            array_fill_keys($this->getChildTables(), 1),
            $this->countChildRows($activeSession),
            'No relation of the active session may be deleted',
        );
    }

    #[Test]
    public function handle_givenExpiredSessions_runsOneDeletePerTableWhateverTheNumberOfSessions(): void
    {
        // Arrange
        foreach (range(1, 3) as $ignored) {
            $expiredSession          = LiveSession::factory()->expired()->create();
            $this->liveSessionIds[]  = $expiredSession->id;
            $this->dungeonRouteIds[] = $expiredSession->dungeon_route_id;

            $this->createOneRowInEveryChildTable($expiredSession);
        }

        /** @var array<int, string> $queries */
        $queries = [];
        DB::listen(static function (QueryExecuted $query) use (&$queries): void {
            // trackTime() records its telemetry through the same connection
            if (!str_contains($query->sql, 'telemetry_metrics')) {
                $queries[] = $query->sql;
            }
        });

        // Act
        $this->artisan(CleanupExpiredLiveSessions::class)->assertSuccessful();

        // Assert
        $this->assertCount(count(LiveSession::CHILD_MODEL_CLASSES), $queries, implode(PHP_EOL, $queries));
        foreach ($queries as $sql) {
            $this->assertStringStartsWith('delete ', $sql);
        }
    }

    private function createOneRowInEveryChildTable(LiveSession $liveSession): void
    {
        LiveSessionOverpulledEnemy::query()->insert([
            'live_session_id' => $liveSession->id,
            'kill_zone_id'    => 999999,
            'npc_id'          => 12345,
            'mdt_id'          => 1,
        ]);
        OverpulledEnemy::query()->insert([
            'live_session_id' => $liveSession->id,
            'kill_zone_id'    => 999999,
            'npc_id'          => 12345,
            'mdt_id'          => 1,
            'enemy_id'        => 0,
        ]);
        LiveSessionEnemyState::factory()->create(['live_session_id' => $liveSession->id]);
        LiveSessionPlayerPosition::factory()->create(['live_session_id' => $liveSession->id]);
        LiveSessionCombatLogBuffer::factory()->create(['live_session_id' => $liveSession->id]);
        LiveSessionCombatLogBatch::factory()->create(['live_session_id' => $liveSession->id]);
    }

    /**
     * @return array<int, string>
     */
    private function getChildTables(): array
    {
        return array_map(
            static fn(string $childModelClass): string => (new $childModelClass())->getTable(),
            LiveSession::CHILD_MODEL_CLASSES,
        );
    }

    /**
     * @return array<string, int>
     */
    private function countChildRows(LiveSession $liveSession): array
    {
        $counts = [];
        foreach ($this->getChildTables() as $childTable) {
            $counts[$childTable] = DB::table($childTable)->where('live_session_id', $liveSession->id)->count();
        }

        return $counts;
    }
}
