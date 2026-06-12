<?php

namespace Tests\Feature\Console\Commands\Scheduler\LiveSession;

use App\Console\Commands\Scheduler\LiveSession\CleanupExpiredLiveSessions;
use App\Models\LiveSession\LiveSession;
use App\Models\LiveSession\LiveSessionCombatLogBuffer;
use App\Models\LiveSession\LiveSessionKilledEnemy;
use App\Models\LiveSession\LiveSessionObsoleteEnemy;
use App\Models\LiveSession\LiveSessionOverpulledEnemy;
use App\Models\LiveSession\LiveSessionPlayerPosition;
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

    #[\Override]
    protected function tearDown(): void
    {
        try {
            if ($this->liveSessionIds !== []) {
                // Delete child records first, then sessions directly via the query builder
                // to avoid triggering the boot() deleting listener (which has a null guard issue on combatLogBuffer).
                DB::table('live_session_overpulled_enemies')->whereIn('live_session_id', $this->liveSessionIds)->delete();
                DB::table('live_session_killed_enemies')->whereIn('live_session_id', $this->liveSessionIds)->delete();
                DB::table('live_session_obsolete_enemies')->whereIn('live_session_id', $this->liveSessionIds)->delete();
                DB::table('live_session_player_positions')->whereIn('live_session_id', $this->liveSessionIds)->delete();
                DB::table('live_session_combat_log_buffers')->whereIn('live_session_id', $this->liveSessionIds)->delete();
                DB::table('live_sessions')->whereIn('id', $this->liveSessionIds)->delete();
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
        $this->liveSessionIds[] = $activeSession->id;

        $killedEnemy = LiveSessionKilledEnemy::factory()->create([
            'live_session_id' => $activeSession->id,
        ]);

        // Act
        $this->artisan(CleanupExpiredLiveSessions::class)->assertSuccessful();

        // Assert
        $this->assertDatabaseHas('live_sessions', ['id' => $activeSession->id]);
        $this->assertDatabaseHas('live_session_killed_enemies', ['id' => $killedEnemy->id]);
    }

    #[Test]
    public function handle_givenExpiredSession_deletesRelationsButKeepsSession(): void
    {
        // Arrange
        $expiredSession         = LiveSession::factory()->expired()->create();
        $this->liveSessionIds[] = $expiredSession->id;

        $overpulledEnemy = LiveSessionOverpulledEnemy::forceCreate([
            'live_session_id' => $expiredSession->id,
            'kill_zone_id'    => 999999,
            'npc_id'          => 12345,
            'mdt_id'          => 1,
        ]);

        $killedEnemy = LiveSessionKilledEnemy::factory()->create([
            'live_session_id' => $expiredSession->id,
        ]);

        $obsoleteEnemy = LiveSessionObsoleteEnemy::factory()->create([
            'live_session_id' => $expiredSession->id,
        ]);

        $playerPosition = LiveSessionPlayerPosition::factory()->create([
            'live_session_id' => $expiredSession->id,
        ]);

        $combatLogBuffer = LiveSessionCombatLogBuffer::factory()->create([
            'live_session_id' => $expiredSession->id,
        ]);

        // Act
        $this->artisan(CleanupExpiredLiveSessions::class)->assertSuccessful();

        // Assert — session row survives
        $this->assertDatabaseHas('live_sessions', ['id' => $expiredSession->id]);

        // Assert — all relation rows are gone
        $this->assertDatabaseMissing('live_session_overpulled_enemies', ['id' => $overpulledEnemy->id]);
        $this->assertDatabaseMissing('live_session_killed_enemies', ['id' => $killedEnemy->id]);
        $this->assertDatabaseMissing('live_session_obsolete_enemies', ['id' => $obsoleteEnemy->id]);
        $this->assertDatabaseMissing('live_session_player_positions', ['id' => $playerPosition->id]);
        $this->assertDatabaseMissing('live_session_combat_log_buffers', ['id' => $combatLogBuffer->id]);
    }
}
