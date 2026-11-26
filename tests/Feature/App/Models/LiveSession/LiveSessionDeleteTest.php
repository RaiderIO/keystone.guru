<?php

namespace Tests\Feature\App\Models\LiveSession;

use App\Models\LiveSession\LiveSession;
use App\Models\LiveSession\LiveSessionCombatLogBatch;
use App\Models\LiveSession\LiveSessionCombatLogBuffer;
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
}
