<?php

namespace Tests\Feature\App\Service\LiveSession;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\LiveSession\LiveSession;
use App\Models\LiveSession\LiveSessionEnemyState;
use App\Models\LiveSession\LiveSessionEnemyStateType;
use App\Models\LiveSession\LiveSessionPlayerPosition;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\LiveSession\LiveSessionCombatStateServiceInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\GeneratesDungeonRoutes;
use Tests\TestCases\PublicTestCase;

#[Group('LiveSession')]
#[Group('LiveSessionCombatStateService')]
final class LiveSessionCombatStateServiceTest extends PublicTestCase
{
    use GeneratesDungeonRoutes;

    private LiveSessionCombatStateServiceInterface $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(LiveSessionCombatStateServiceInterface::class);
    }

    // -------------------------------------------------------------------------
    // Killed enemies
    // -------------------------------------------------------------------------

    #[Test]
    public function setKilledEnemy_givenNewEnemy_persistsKilledRow(): void
    {
        // Arrange
        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();

        try {
            // Act
            $this->service->setKilledEnemy($liveSession, 12345, 7);

            // Assert
            $this->assertDatabaseHas('live_session_enemy_states', [
                'live_session_id' => $liveSession->id,
                'state'           => LiveSessionEnemyStateType::Killed->value,
                'npc_id'          => 12345,
                'mdt_id'          => 7,
            ]);
        } finally {
            $this->cleanupSession($liveSession);
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
            $this->cleanupSession($liveSession);
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
            $this->cleanupSession($liveSession);
        }
    }

    #[Test]
    public function setKilledEnemy_givenDuplicateEnemy_keepsOneRowWithoutThrowing(): void
    {
        // Arrange
        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();

        try {
            $this->service->setKilledEnemy($liveSession, 12345, 7);

            // Act
            $exception = null;

            try {
                $this->service->setKilledEnemy($liveSession, 12345, 7);
            } catch (QueryException $queryException) {
                $exception = $queryException;
            }

            // Assert
            $this->assertNull($exception, 'Recording a kill that is already recorded must be a no-op, not a failing insert');
            $this->assertSame(1, LiveSessionEnemyState::query()
                ->where('live_session_id', $liveSession->id)
                ->where('state', LiveSessionEnemyStateType::Killed)
                ->where('npc_id', 12345)
                ->where('mdt_id', 7)
                ->count());
        } finally {
            $this->cleanupSession($liveSession);
        }
    }

    #[Test]
    public function setKilledEnemy_givenEnemyInCombat_recordsTheKillNextToTheInCombatRow(): void
    {
        // Arrange
        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();

        try {
            LiveSessionEnemyState::factory()->inState(LiveSessionEnemyStateType::InCombat)->create([
                'live_session_id' => $liveSession->id,
                'npc_id'          => 12345,
                'mdt_id'          => 7,
            ]);

            // Act
            $result = $this->service->setKilledEnemy($liveSession, 12345, 7);

            // Assert
            $this->assertTrue($result, 'An enemy in combat has not been killed yet');
            $this->assertSame(
                [LiveSessionEnemyStateType::InCombat->value, LiveSessionEnemyStateType::Killed->value],
                LiveSessionEnemyState::query()
                    ->where('live_session_id', $liveSession->id)
                    ->orderBy('id')
                    ->pluck('state')
                    ->map(static fn(LiveSessionEnemyStateType $state): string => $state->value)
                    ->all(),
            );
        } finally {
            $this->cleanupSession($liveSession);
        }
    }

    // -------------------------------------------------------------------------
    // Enemy ids per state
    // -------------------------------------------------------------------------

    #[Test]
    public function getEnemyIds_givenOnlyAnotherSessionsEnemies_returnsEmptyCollectionForEveryState(): void
    {
        // Arrange
        [$liveSession, $otherLiveSession, $enemies] = $this->arrangeTwoSessionsOnOneRoute();

        try {
            /** @var Enemy $enemy */
            $enemy = $enemies->firstOrFail();
            $this->service->setKilledEnemy($otherLiveSession, $enemy->npc_id, $enemy->mdt_id);
            $this->service->replaceEnemies($otherLiveSession, LiveSessionEnemyStateType::InCombat, collect([$enemy]));
            $this->service->replaceEnemies($otherLiveSession, LiveSessionEnemyStateType::Obsolete, collect([$enemy]));

            foreach (LiveSessionEnemyStateType::cases() as $state) {
                // Act
                $result             = $this->service->getEnemyIds($liveSession, $state);
                $otherSessionResult = $this->service->getEnemyIds($otherLiveSession, $state);

                // Assert
                $this->assertTrue($result->isEmpty(), $state->value);
                $this->assertSame([$enemy->id], $otherSessionResult->all(), $state->value);
            }
        } finally {
            $this->cleanupSession($liveSession, $otherLiveSession);
        }
    }

    #[Test]
    public function getEnemyIds_givenEnemiesInEveryState_returnsOnlyTheRequestedStateOfThisSession(): void
    {
        // Arrange
        [$liveSession, $otherLiveSession, $enemies] = $this->arrangeTwoSessionsOnOneRoute();

        try {
            $this->assertCount(4, $enemies, 'The route needs four distinct enemies');
            [$killedEnemy, $inCombatEnemy, $obsoleteEnemy, $otherSessionsEnemy] = $enemies->all();

            $this->service->setKilledEnemy($liveSession, $killedEnemy->npc_id, $killedEnemy->mdt_id);
            $this->service->replaceEnemies($liveSession, LiveSessionEnemyStateType::InCombat, collect([$inCombatEnemy]));
            $this->service->replaceEnemies($liveSession, LiveSessionEnemyStateType::Obsolete, collect([$obsoleteEnemy]));
            $this->service->setKilledEnemy($otherLiveSession, $otherSessionsEnemy->npc_id, $otherSessionsEnemy->mdt_id);

            // Act
            $killedEnemyIds   = $this->service->getEnemyIds($liveSession, LiveSessionEnemyStateType::Killed);
            $inCombatEnemyIds = $this->service->getEnemyIds($liveSession, LiveSessionEnemyStateType::InCombat);
            $obsoleteEnemyIds = $this->service->getEnemyIds($liveSession, LiveSessionEnemyStateType::Obsolete);

            // Assert
            $this->assertSame([$killedEnemy->id], $killedEnemyIds->all());
            $this->assertSame([$inCombatEnemy->id], $inCombatEnemyIds->all());
            $this->assertSame([$obsoleteEnemy->id], $obsoleteEnemyIds->all());
        } finally {
            $this->cleanupSession($liveSession, $otherLiveSession);
        }
    }

    #[Test]
    public function getEnemyIds_givenTheSameNpcAndMdtIdOnAnotherMappingVersion_returnsOnlyTheRoutesEnemy(): void
    {
        // Arrange
        [$liveSession, $otherLiveSession, $enemies] = $this->arrangeTwoSessionsOnOneRoute();
        $twinOnAnotherMappingVersion                = null;

        try {
            /** @var Enemy $killedEnemy */
            $killedEnemy = $enemies->firstOrFail();
            /** @var Enemy $twinOnAnotherMappingVersion */
            $twinOnAnotherMappingVersion = Enemy::factory()->create([
                'npc_id' => $killedEnemy->npc_id,
                'mdt_id' => $killedEnemy->mdt_id,
            ]);
            $this->service->setKilledEnemy($liveSession, $killedEnemy->npc_id, $killedEnemy->mdt_id);

            // Act
            $result = $this->service->getEnemyIds($liveSession, LiveSessionEnemyStateType::Killed);

            // Assert
            $this->assertSame([$killedEnemy->id], $result->all());
        } finally {
            $twinOnAnotherMappingVersion?->delete();
            $this->cleanupSession($liveSession, $otherLiveSession);
        }
    }

    #[Test]
    public function hasEnemies_givenOnlyAnInCombatEnemy_isTrueForInCombatOnly(): void
    {
        // Arrange
        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();

        try {
            LiveSessionEnemyState::factory()->inState(LiveSessionEnemyStateType::InCombat)->create([
                'live_session_id' => $liveSession->id,
            ]);

            // Act
            $hasInCombatEnemies = $this->service->hasEnemies($liveSession, LiveSessionEnemyStateType::InCombat);
            $hasObsoleteEnemies = $this->service->hasEnemies($liveSession, LiveSessionEnemyStateType::Obsolete);

            // Assert
            $this->assertTrue($hasInCombatEnemies);
            $this->assertFalse($hasObsoleteEnemies);
        } finally {
            $this->cleanupSession($liveSession);
        }
    }

    // -------------------------------------------------------------------------
    // Replacing a state
    // -------------------------------------------------------------------------

    #[Test]
    public function replaceEnemies_givenNewEnemies_persistsRecords(): void
    {
        // Arrange
        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();

        /** @var Collection<int, Enemy> $enemies */
        $enemies = Enemy::factory()->count(2)->create();

        try {
            // Act
            $this->service->replaceEnemies($liveSession, LiveSessionEnemyStateType::Obsolete, $enemies);

            // Assert
            $this->assertSame(2, LiveSessionEnemyState::query()
                ->where('live_session_id', $liveSession->id)
                ->where('state', LiveSessionEnemyStateType::Obsolete)
                ->count());
        } finally {
            Enemy::whereIn('id', $enemies->pluck('id'))->delete();
            $this->cleanupSession($liveSession);
        }
    }

    #[Test]
    public function replaceEnemies_givenExistingRecords_replacesFullSet(): void
    {
        // Arrange
        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();

        /** @var Collection<int, Enemy> $enemies */
        $enemies = Enemy::factory()->count(2)->create();
        /** @var Enemy $oldEnemy */
        $oldEnemy = Enemy::factory()->create();

        try {
            $this->service->replaceEnemies($liveSession, LiveSessionEnemyStateType::InCombat, collect([
                $oldEnemy,
            ]));

            // Act — replace with a completely different set
            $this->service->replaceEnemies($liveSession, LiveSessionEnemyStateType::InCombat, $enemies);

            // Assert — old record is gone, only new records remain
            $this->assertDatabaseMissing('live_session_enemy_states', [
                'live_session_id' => $liveSession->id,
                'npc_id'          => $oldEnemy->npc_id,
            ]);
            $this->assertSame($enemies->count(), LiveSessionEnemyState::query()
                ->where('live_session_id', $liveSession->id)
                ->where('state', LiveSessionEnemyStateType::InCombat)
                ->count());
        } finally {
            Enemy::whereIn('id', $enemies->pluck('id')->push($oldEnemy->id))->delete();
            $this->cleanupSession($liveSession);
        }
    }

    #[Test]
    public function replaceEnemies_givenEmptyCollection_clearsExistingRecords(): void
    {
        // Arrange
        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();

        /** @var Enemy $enemy */
        $enemy = Enemy::factory()->create();

        try {
            $this->service->replaceEnemies($liveSession, LiveSessionEnemyStateType::InCombat, collect([
                $enemy,
            ]));

            // Act
            $this->service->replaceEnemies($liveSession, LiveSessionEnemyStateType::InCombat, collect());

            // Assert
            $this->assertSame(0, LiveSessionEnemyState::query()
                ->where('live_session_id', $liveSession->id)
                ->count());
        } finally {
            $enemy->delete();
            $this->cleanupSession($liveSession);
        }
    }

    #[Test]
    public function replaceEnemies_givenRowsInOtherStates_leavesThemAlone(): void
    {
        // Arrange
        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();

        /** @var Enemy $inCombatEnemy */
        $inCombatEnemy = Enemy::factory()->create();

        try {
            $this->service->setKilledEnemy($liveSession, 12345, 7);
            $this->service->replaceEnemies($liveSession, LiveSessionEnemyStateType::Obsolete, collect([$inCombatEnemy]));

            // Act
            $this->service->replaceEnemies($liveSession, LiveSessionEnemyStateType::InCombat, collect([$inCombatEnemy]));

            // Assert
            $this->assertEqualsCanonicalizing(
                [LiveSessionEnemyStateType::Killed->value, LiveSessionEnemyStateType::InCombat->value, LiveSessionEnemyStateType::Obsolete->value],
                LiveSessionEnemyState::query()
                    ->where('live_session_id', $liveSession->id)
                    ->pluck('state')
                    ->map(static fn(LiveSessionEnemyStateType $state): string => $state->value)
                    ->all(),
            );
        } finally {
            $inCombatEnemy->delete();
            $this->cleanupSession($liveSession);
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
            $this->cleanupSession($liveSession);
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
            $this->cleanupSession($liveSession);
        }
    }

    #[Test]
    public function setPlayerPosition_givenZeroCoordinates_persistsThemRatherThanDroppingThem(): void
    {
        // Arrange - a lat/lng of exactly 0.0 is falsy; a naive array_filter() would silently strip it
        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();

        try {
            // Act
            $this->service->setPlayerPosition($liveSession, 'Player-1234-ABCDEF01', 'Testchar', 0.0, 0.0, 1);

            // Assert
            $position = LiveSessionPlayerPosition::query()
                ->where('live_session_id', $liveSession->id)
                ->where('player_guid', 'Player-1234-ABCDEF01')
                ->firstOrFail();
            $this->assertSame(0.0, $position->lat);
            $this->assertSame(0.0, $position->lng);
        } finally {
            LiveSessionPlayerPosition::query()->where('live_session_id', $liveSession->id)->delete();
            $this->cleanupSession($liveSession);
        }
    }

    #[Test]
    public function setPlayerPosition_givenClassAndSpec_persistsThem(): void
    {
        // Arrange
        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();

        try {
            // Act
            $this->service->setPlayerPosition($liveSession, 'Player-1234-ABCDEF01', 'Testchar', -123.5, 45.2, 1, 7, 262);

            // Assert
            $this->assertDatabaseHas('live_session_player_positions', [
                'live_session_id'   => $liveSession->id,
                'player_guid'       => 'Player-1234-ABCDEF01',
                'class_id'          => 7,
                'specialization_id' => 262,
            ]);
        } finally {
            LiveSessionPlayerPosition::query()->where('live_session_id', $liveSession->id)->delete();
            $this->cleanupSession($liveSession);
        }
    }

    #[Test]
    public function setPlayerPosition_givenNullClassAndSpec_keepsExistingValues(): void
    {
        // Arrange
        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();

        try {
            $this->service->setPlayerPosition($liveSession, 'Player-1234-ABCDEF01', 'Testchar', -100.0, 20.0, 1, 7, 262);

            // Act — a later position update without a resolvable COMBATANT_INFO
            $this->service->setPlayerPosition($liveSession, 'Player-1234-ABCDEF01', 'Testchar', -200.0, 50.0, 2);

            // Assert — the class/spec resolved earlier is preserved
            $this->assertDatabaseHas('live_session_player_positions', [
                'live_session_id'   => $liveSession->id,
                'player_guid'       => 'Player-1234-ABCDEF01',
                'floor_id'          => 2,
                'class_id'          => 7,
                'specialization_id' => 262,
            ]);
        } finally {
            LiveSessionPlayerPosition::query()->where('live_session_id', $liveSession->id)->delete();
            $this->cleanupSession($liveSession);
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
            $this->cleanupSession($liveSession);
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
            $this->cleanupSession($liveSession);
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
        /** @var LiveSession $otherLiveSession */
        $otherLiveSession = LiveSession::factory()->create();

        /** @var Enemy $enemy */
        $enemy = Enemy::factory()->create();

        try {
            $this->service->setKilledEnemy($liveSession, 12345, 1);
            $this->service->replaceEnemies($liveSession, LiveSessionEnemyStateType::Obsolete, collect([$enemy]));
            $this->service->replaceEnemies($liveSession, LiveSessionEnemyStateType::InCombat, collect([$enemy]));
            $this->service->setPlayerPosition($liveSession, 'Player-1-AAAAAAAA', 'Alice', 0.0, 0.0, 1);
            $this->service->setKilledEnemy($otherLiveSession, 12345, 1);

            // Act
            $liveSession->delete();

            // Assert
            $this->assertDatabaseMissing('live_session_enemy_states', ['live_session_id' => $liveSession->id]);
            $this->assertDatabaseMissing('live_session_player_positions', ['live_session_id' => $liveSession->id]);
            $this->assertDatabaseHas('live_session_enemy_states', ['live_session_id' => $otherLiveSession->id]);
        } finally {
            $enemy->delete();
            $this->cleanupSession($liveSession, $otherLiveSession);
        }
    }

    /**
     * Two sessions on one route that carries enemies, and four distinct enemies of that route.
     *
     * @return array{LiveSession, LiveSession, Collection<int, Enemy>}
     */
    private function arrangeTwoSessionsOnOneRoute(): array
    {
        $dungeonRoute = $this->createNonFacadeDungeonRouteWithEnemies();

        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create(['dungeon_route_id' => $dungeonRoute->id]);
        /** @var LiveSession $otherLiveSession */
        $otherLiveSession = LiveSession::factory()->create(['dungeon_route_id' => $dungeonRoute->id]);

        /** @var Collection<int, Enemy> $enemies */
        $enemies = Enemy::query()
            ->where('mapping_version_id', $dungeonRoute->mapping_version_id)
            ->whereNotNull('npc_id')
            ->whereNotNull('mdt_id')
            ->orderBy('id')
            ->get()
            ->unique(static fn(Enemy $enemy): string => sprintf('%d-%d', $enemy->npc_id, $enemy->mdt_id))
            ->take(4)
            ->values();

        return [$liveSession, $otherLiveSession, $enemies];
    }

    private function cleanupSession(LiveSession ...$liveSessions): void
    {
        $dungeonRouteIds = [];
        foreach ($liveSessions as $liveSession) {
            $dungeonRouteIds[] = $liveSession->dungeon_route_id;
            $liveSession->delete();
        }

        DungeonRoute::query()->whereIn('id', array_unique($dungeonRouteIds))->get()
            ->each(static fn(DungeonRoute $dungeonRoute) => $dungeonRoute->delete());
    }
}
