<?php

namespace Tests\Feature\App\Models\LiveSession;

use App\Models\Enemies\OverpulledEnemy;
use App\Models\LiveSession\LiveSession;
use App\Models\LiveSession\LiveSessionCombatLogBatch;
use App\Models\LiveSession\LiveSessionCombatLogBuffer;
use App\Models\LiveSession\LiveSessionEnemyState;
use App\Models\LiveSession\LiveSessionEnemyStateType;
use App\Models\LiveSession\LiveSessionOverpulledEnemy;
use App\Models\LiveSession\LiveSessionPlayerPosition;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('LiveSession')]
final class LiveSessionDeleteTest extends PublicTestCase
{
    #[Test]
    public function delete_givenACheckpointAndBatches_deletesThemWithTheSession(): void
    {
        // Arrange
        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();
        /** @var LiveSession $otherLiveSession */
        $otherLiveSession = LiveSession::factory()->create();

        try {
            LiveSessionCombatLogBuffer::factory()->create(['live_session_id' => $liveSession->id]);
            LiveSessionCombatLogBatch::factory()->count(2)->create(['live_session_id' => $liveSession->id]);
            LiveSessionCombatLogBatch::factory()->create(['live_session_id' => $otherLiveSession->id]);

            // Act
            $liveSession->delete();

            // Assert
            $this->assertFalse(LiveSessionCombatLogBuffer::query()->where('live_session_id', $liveSession->id)->exists());
            $this->assertFalse(LiveSessionCombatLogBatch::query()->where('live_session_id', $liveSession->id)->exists());
            $this->assertTrue(LiveSessionCombatLogBatch::query()->where('live_session_id', $otherLiveSession->id)->exists());
        } finally {
            LiveSessionCombatLogBuffer::query()->whereIn('live_session_id', [$liveSession->id, $otherLiveSession->id])->delete();
            LiveSessionCombatLogBatch::query()->whereIn('live_session_id', [$liveSession->id, $otherLiveSession->id])->delete();
            LiveSession::query()->whereIn('id', [$liveSession->id, $otherLiveSession->id])->delete();
            $liveSession->dungeonRoute?->delete();
            $otherLiveSession->dungeonRoute?->delete();
        }
    }

    #[Test]
    public function delete_givenRowsInEveryChildTable_deletesOnlyThisSessionsRows(): void
    {
        // Arrange
        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();
        /** @var LiveSession $otherLiveSession */
        $otherLiveSession = LiveSession::factory()->create();

        try {
            foreach ([$liveSession, $otherLiveSession] as $session) {
                LiveSessionCombatLogBuffer::factory()->create(['live_session_id' => $session->id]);
                LiveSessionCombatLogBatch::factory()->create(['live_session_id' => $session->id]);
                LiveSessionOverpulledEnemy::query()->insert([
                    'live_session_id' => $session->id,
                    'kill_zone_id'    => 999999,
                    'npc_id'          => 12345,
                    'mdt_id'          => 1,
                ]);
                OverpulledEnemy::query()->insert([
                    'live_session_id' => $session->id,
                    'kill_zone_id'    => 999999,
                    'npc_id'          => 12345,
                    'mdt_id'          => 1,
                    'enemy_id'        => 0,
                ]);
                foreach (LiveSessionEnemyStateType::cases() as $state) {
                    LiveSessionEnemyState::factory()->inState($state)->create(['live_session_id' => $session->id]);
                }
                LiveSessionPlayerPosition::factory()->create(['live_session_id' => $session->id]);
            }

            // Act
            $liveSession->delete();

            // Assert
            $this->assertSame([
                'live_session_combat_log_buffers' => [0, 1],
                'live_session_combat_log_batches' => [0, 1],
                'live_session_overpulled_enemies' => [0, 1],
                'overpulled_enemies'              => [0, 1],
                'live_session_enemy_states'       => [0, 3],
                'live_session_player_positions'   => [0, 1],
            ], collect(LiveSession::CHILD_MODEL_CLASSES)
                ->mapWithKeys(static fn(string $childModelClass): array => [
                    (new $childModelClass())->getTable() => [
                        $childModelClass::query()->where('live_session_id', $liveSession->id)->count(),
                        $childModelClass::query()->where('live_session_id', $otherLiveSession->id)->count(),
                    ],
                ])
                ->all());
        } finally {
            $liveSession->delete();
            $otherLiveSession->delete();
            $liveSession->dungeonRoute?->delete();
            $otherLiveSession->dungeonRoute?->delete();
        }
    }
}
