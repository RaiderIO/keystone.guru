<?php

namespace Tests\Feature\App\Service\LiveSession;

use App\Models\LiveSession\LiveSession;
use App\Models\LiveSession\LiveSessionKilledEnemy;
use App\Models\LiveSession\LiveSessionObsoleteEnemy;
use App\Models\LiveSession\LiveSessionPlayerPosition;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\LiveSession\LiveSessionCombatStateService;
use App\Service\LiveSession\LiveSessionCombatStateServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('LiveSession')]
#[Group('LiveSessionCombatStateService')]
final class LiveSessionCombatStateServiceTest extends PublicTestCase
{
    private LiveSessionCombatStateServiceInterface $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new LiveSessionCombatStateService();
    }

    // -------------------------------------------------------------------------
    // Killed enemies
    // -------------------------------------------------------------------------

    #[Test]
    public function setKilledEnemy_givenNewEnemy_persistsRecord(): void
    {
        // Arrange
        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();

        try {
            // Act
            $this->service->setKilledEnemy($liveSession, 12345, 7);

            // Assert
            $this->assertDatabaseHas('live_session_killed_enemies', [
                'live_session_id' => $liveSession->id,
                'npc_id'          => 12345,
                'mdt_id'          => 7,
            ]);
        } finally {
            LiveSessionKilledEnemy::query()->where('live_session_id', $liveSession->id)->delete();
            $liveSession->delete();
            $liveSession->dungeonRoute?->delete();
        }
    }

    #[Test]
    public function setKilledEnemy_givenNewEnemy_returnsTrue(): void
    {
        // Arrange
        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();

        try {
            // Act
            $result = $this->service->setKilledEnemy($liveSession, 12345, 7);

            // Assert
            $this->assertTrue($result);
        } finally {
            LiveSessionKilledEnemy::query()->where('live_session_id', $liveSession->id)->delete();
            $liveSession->delete();
            $liveSession->dungeonRoute?->delete();
        }
    }

    #[Test]
    public function setKilledEnemy_givenExistingEnemy_returnsFalse(): void
    {
        // Arrange
        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();

        try {
            $this->service->setKilledEnemy($liveSession, 12345, 7);

            // Act
            $result = $this->service->setKilledEnemy($liveSession, 12345, 7);

            // Assert
            $this->assertFalse($result);
        } finally {
            LiveSessionKilledEnemy::query()->where('live_session_id', $liveSession->id)->delete();
            $liveSession->delete();
            $liveSession->dungeonRoute?->delete();
        }
    }

    #[Test]
    public function setKilledEnemy_givenDuplicateEnemy_doesNotCreateDuplicate(): void
    {
        // Arrange
        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();

        try {
            // Act
            $this->service->setKilledEnemy($liveSession, 12345, 7);
            $this->service->setKilledEnemy($liveSession, 12345, 7);

            // Assert
            $this->assertSame(1, LiveSessionKilledEnemy::query()
                ->where('live_session_id', $liveSession->id)
                ->where('npc_id', 12345)
                ->where('mdt_id', 7)
                ->count());
        } finally {
            LiveSessionKilledEnemy::query()->where('live_session_id', $liveSession->id)->delete();
            $liveSession->delete();
            $liveSession->dungeonRoute?->delete();
        }
    }

    #[Test]
    public function getKilledEnemyIds_givenNoKilledEnemies_returnsEmptyCollection(): void
    {
        // Arrange
        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();

        try {
            // Act
            $result = $liveSession->mapContextKilledEnemyIds();

            // Assert
            $this->assertTrue($result->isEmpty());
        } finally {
            $liveSession->delete();
            $liveSession->dungeonRoute?->delete();
        }
    }

    // -------------------------------------------------------------------------
    // Obsolete enemies
    // -------------------------------------------------------------------------

    #[Test]
    public function replaceObsoleteEnemies_givenNewPairs_persistsRecords(): void
    {
        // Arrange
        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();

        try {
            // Act
            $this->service->replaceObsoleteEnemies($liveSession, [
                ['npc_id' => 11111, 'mdt_id' => 1],
                ['npc_id' => 22222, 'mdt_id' => 2],
            ]);

            // Assert
            $this->assertSame(2, LiveSessionObsoleteEnemy::query()
                ->where('live_session_id', $liveSession->id)
                ->count());
        } finally {
            LiveSessionObsoleteEnemy::query()->where('live_session_id', $liveSession->id)->delete();
            $liveSession->delete();
            $liveSession->dungeonRoute?->delete();
        }
    }

    #[Test]
    public function replaceObsoleteEnemies_givenExistingRecords_replacesFullSet(): void
    {
        // Arrange
        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();

        try {
            $this->service->replaceObsoleteEnemies($liveSession, [
                ['npc_id' => 11111, 'mdt_id' => 1],
            ]);

            // Act — replace with a completely different set
            $this->service->replaceObsoleteEnemies($liveSession, [
                ['npc_id' => 33333, 'mdt_id' => 3],
                ['npc_id' => 44444, 'mdt_id' => 4],
            ]);

            // Assert — old record is gone, only new records remain
            $this->assertDatabaseMissing('live_session_obsolete_enemies', [
                'live_session_id' => $liveSession->id,
                'npc_id'          => 11111,
            ]);
            $this->assertSame(2, LiveSessionObsoleteEnemy::query()
                ->where('live_session_id', $liveSession->id)
                ->count());
        } finally {
            LiveSessionObsoleteEnemy::query()->where('live_session_id', $liveSession->id)->delete();
            $liveSession->delete();
            $liveSession->dungeonRoute?->delete();
        }
    }

    #[Test]
    public function getObsoleteEnemyIds_givenNoObsoleteEnemies_returnsEmptyCollection(): void
    {
        // Arrange
        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();

        try {
            // Act
            $result = $this->service->getObsoleteEnemyIds($liveSession);

            // Assert
            $this->assertTrue($result->isEmpty());
        } finally {
            $liveSession->delete();
            $liveSession->dungeonRoute?->delete();
        }
    }

    // -------------------------------------------------------------------------
    // Player positions
    // -------------------------------------------------------------------------

    #[Test]
    public function setPlayerPosition_givenNewPlayer_persistsRecord(): void
    {
        // Arrange
        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();

        try {
            // Act
            $this->service->setPlayerPosition($liveSession, 'Player-1234-ABCDEF01', 'Testchar', -123.5, 45.2, 1);

            // Assert
            $this->assertDatabaseHas('live_session_player_positions', [
                'live_session_id' => $liveSession->id,
                'player_guid'     => 'Player-1234-ABCDEF01',
                'character_name'  => 'Testchar',
                'floor_id'        => 1,
            ]);
        } finally {
            LiveSessionPlayerPosition::query()->where('live_session_id', $liveSession->id)->delete();
            $liveSession->delete();
            $liveSession->dungeonRoute?->delete();
        }
    }

    #[Test]
    public function setPlayerPosition_givenExistingPlayer_updatesRecord(): void
    {
        // Arrange
        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();

        try {
            $this->service->setPlayerPosition($liveSession, 'Player-1234-ABCDEF01', 'Testchar', -100.0, 20.0, 1);

            // Act — update with new coordinates
            $this->service->setPlayerPosition($liveSession, 'Player-1234-ABCDEF01', 'Testchar', -200.0, 50.0, 2);

            // Assert — only one record, with updated coordinates
            $this->assertSame(1, LiveSessionPlayerPosition::query()
                ->where('live_session_id', $liveSession->id)
                ->where('player_guid', 'Player-1234-ABCDEF01')
                ->count());

            $this->assertDatabaseHas('live_session_player_positions', [
                'live_session_id' => $liveSession->id,
                'player_guid'     => 'Player-1234-ABCDEF01',
                'floor_id'        => 2,
            ]);
        } finally {
            LiveSessionPlayerPosition::query()->where('live_session_id', $liveSession->id)->delete();
            $liveSession->delete();
            $liveSession->dungeonRoute?->delete();
        }
    }

    #[Test]
    public function mapContextPlayerPositions_givenMultiplePlayers_returnsAllPositions(): void
    {
        // Arrange
        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();

        try {
            $this->service->setPlayerPosition($liveSession, 'Player-1-AAAAAAAA', 'Alice', -100.0, 20.0, 1);
            $this->service->setPlayerPosition($liveSession, 'Player-2-BBBBBBBB', 'Bob', -150.0, 30.0, 1);

            // Act
            $positions = $liveSession->mapContextPlayerPositions(app(CoordinatesServiceInterface::class), false);

            // Assert
            $this->assertCount(2, $positions);

            $guids = $positions->pluck('player_guid')->all();
            $this->assertContains('Player-1-AAAAAAAA', $guids);
            $this->assertContains('Player-2-BBBBBBBB', $guids);
        } finally {
            LiveSessionPlayerPosition::query()->where('live_session_id', $liveSession->id)->delete();
            $liveSession->delete();
            $liveSession->dungeonRoute?->delete();
        }
    }

    #[Test]
    public function mapContextPlayerPositions_givenNoPositions_returnsEmptyCollection(): void
    {
        // Arrange
        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();

        try {
            // Act
            $positions = $liveSession->mapContextPlayerPositions(app(CoordinatesServiceInterface::class), false);

            // Assert
            $this->assertTrue($positions->isEmpty());
        } finally {
            $liveSession->delete();
            $liveSession->dungeonRoute?->delete();
        }
    }

    // -------------------------------------------------------------------------
    // Cascade delete
    // -------------------------------------------------------------------------

    #[Test]
    public function liveSessionDelete_givenRelatedRecords_deletesAllCombatState(): void
    {
        // Arrange
        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();

        $this->service->setKilledEnemy($liveSession, 12345, 1);
        $this->service->replaceObsoleteEnemies($liveSession, [['npc_id' => 99999, 'mdt_id' => 5]]);
        $this->service->setPlayerPosition($liveSession, 'Player-1-AAAAAAAA', 'Alice', 0.0, 0.0, 1);

        $dungeonRoute = $liveSession->dungeonRoute;

        try {
            // Act
            $liveSession->delete();

            // Assert
            $this->assertDatabaseMissing('live_session_killed_enemies', ['live_session_id' => $liveSession->id]);
            $this->assertDatabaseMissing('live_session_obsolete_enemies', ['live_session_id' => $liveSession->id]);
            $this->assertDatabaseMissing('live_session_player_positions', ['live_session_id' => $liveSession->id]);
        } finally {
            LiveSessionKilledEnemy::query()->where('live_session_id', $liveSession->id)->delete();
            LiveSessionObsoleteEnemy::query()->where('live_session_id', $liveSession->id)->delete();
            LiveSessionPlayerPosition::query()->where('live_session_id', $liveSession->id)->delete();
            $dungeonRoute?->delete();
        }
    }
}
