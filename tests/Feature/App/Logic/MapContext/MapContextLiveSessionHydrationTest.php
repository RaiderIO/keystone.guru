<?php

namespace Tests\Feature\App\Logic\MapContext;

use App\Logic\MapContext\Map\MapContextLiveSession;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemies\OverpulledEnemy;
use App\Models\Enemy;
use App\Models\LiveSession\LiveSession;
use App\Models\LiveSession\LiveSessionEnemyState;
use App\Models\LiveSession\LiveSessionEnemyStateType;
use App\Models\LiveSession\LiveSessionOverpulledEnemy;
use App\Models\LiveSession\LiveSessionPlayerPosition;
use App\Models\User;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\KillZonePath\KillZonePathServiceInterface;
use App\Service\LiveSession\DungeonRouteCorrection;
use App\Service\LiveSession\LiveSessionCombatStateServiceInterface;
use App\Service\LiveSession\OverpulledEnemyServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\GeneratesDungeonRoutes;
use Tests\TestCases\PublicTestCase;

#[Group('MapContext')]
#[Group('MapContextLiveSession')]
final class MapContextLiveSessionHydrationTest extends PublicTestCase
{
    use GeneratesDungeonRoutes;

    private function makeContext(LiveSession $liveSession, ?OverpulledEnemyServiceInterface $overpulledEnemyService = null): MapContextLiveSession
    {
        return new MapContextLiveSession(
            app(CacheServiceInterface::class),
            app(CoordinatesServiceInterface::class),
            app(KillZonePathServiceInterface::class),
            $overpulledEnemyService ?? app(OverpulledEnemyServiceInterface::class),
            app(LiveSessionCombatStateServiceInterface::class),
            $liveSession,
            User::MAP_FACADE_STYLE_SPLIT_FLOORS,
        );
    }

    #[Test]
    public function toArray_givenKilledEnemy_includesResolvedEnemyIdInKilledEnemies(): void
    {
        // Arrange — find a route whose mapping version has at least one enemy with npc_id + mdt_id
        $dungeonRoute = $this->createNonFacadeDungeonRouteWithEnemies();

        /** @var Enemy|null $enemy */
        $enemy = Enemy::query()
            ->where('mapping_version_id', $dungeonRoute->mapping_version_id)
            ->whereNotNull('npc_id')
            ->whereNotNull('mdt_id')
            ->first();

        if ($enemy === null) {
            $this->markTestSkipped('No enemy with npc_id+mdt_id found for this mapping version.');
        }

        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create(['dungeon_route_id' => $dungeonRoute->id]);

        try {
            LiveSessionEnemyState::query()->create([
                'live_session_id' => $liveSession->id,
                'state'           => LiveSessionEnemyStateType::Killed,
                'npc_id'          => $enemy->npc_id,
                'mdt_id'          => $enemy->mdt_id,
            ]);

            // Act
            $array = $this->makeContext($liveSession)->toArray();

            // Assert
            $this->assertArrayHasKey('killedEnemies', $array);
            $this->assertContains($enemy->id, $array['killedEnemies']->all());
        } finally {
            LiveSessionEnemyState::query()->where('live_session_id', $liveSession->id)->delete();
            $liveSession->delete();
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function toArray_givenStoredObsoleteRowOutsideTheDerivedSet_returnsOnlyTheDerivedObsoleteEnemies(): void
    {
        // Arrange
        $dungeonRoute = $this->createNonFacadeDungeonRouteWithEnemies();

        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create(['dungeon_route_id' => $dungeonRoute->id]);

        try {
            /** @var array<int, Enemy> $enemies */
            $enemies = Enemy::query()
                ->where('mapping_version_id', $dungeonRoute->mapping_version_id)
                ->whereNotNull('npc_id')
                ->whereNotNull('mdt_id')
                ->orderBy('id')
                ->get()
                ->unique(static fn(Enemy $enemy): string => sprintf('%d-%d', $enemy->npc_id, $enemy->mdt_id))
                ->take(2)
                ->values()
                ->all();
            $this->assertCount(2, $enemies, 'The route needs two distinct enemies');
            [$derivedObsoleteEnemy, $staleObsoleteEnemy] = $enemies;

            // The obsolete set a buffer pass last broadcast, since made stale by e.g. an overpull being undone
            LiveSessionEnemyState::query()->create([
                'live_session_id' => $liveSession->id,
                'state'           => LiveSessionEnemyStateType::Obsolete,
                'npc_id'          => $staleObsoleteEnemy->npc_id,
                'mdt_id'          => $staleObsoleteEnemy->mdt_id,
            ]);

            $routeCorrection = new DungeonRouteCorrection($liveSession);
            $routeCorrection->addObsoleteEnemy($derivedObsoleteEnemy->id);
            $overpulledEnemyService = $this->createMock(OverpulledEnemyServiceInterface::class);
            $overpulledEnemyService->method('getRouteCorrection')->willReturn($routeCorrection);

            // Act
            $array = $this->makeContext($liveSession, $overpulledEnemyService)->toArray();

            // Assert
            $this->assertArrayHasKey('obsoleteEnemies', $array);
            $this->assertSame([$derivedObsoleteEnemy->id], $array['obsoleteEnemies']->all());
        } finally {
            LiveSessionEnemyState::query()->where('live_session_id', $liveSession->id)->delete();
            $liveSession->delete();
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function toArray_givenOverpulledEnemy_includesEnemyIdAndKillZoneIdObject(): void
    {
        // Arrange
        $dungeonRoute = $this->createNonFacadeDungeonRouteWithEnemies();

        /** @var Enemy|null $enemy */
        $enemy = Enemy::query()
            ->where('mapping_version_id', $dungeonRoute->mapping_version_id)
            ->whereNotNull('npc_id')
            ->whereNotNull('mdt_id')
            ->first();

        if ($enemy === null) {
            $this->markTestSkipped('No enemy with npc_id+mdt_id found for this mapping version.');
        }

        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create(['dungeon_route_id' => $dungeonRoute->id]);

        try {
            LiveSessionOverpulledEnemy::query()->create([
                'live_session_id' => $liveSession->id,
                'kill_zone_id'    => 4242,
                'npc_id'          => $enemy->npc_id,
                'mdt_id'          => $enemy->mdt_id,
            ]);

            // Act
            $array = $this->makeContext($liveSession)->toArray();

            // Assert — the frontend restores overpulled state from {enemy_id, kill_zone_id} objects
            $this->assertArrayHasKey('overpulledEnemies', $array);
            $this->assertContains(['enemy_id' => $enemy->id, 'kill_zone_id' => 4242], $array['overpulledEnemies']->all());

            $entry = $array['overpulledEnemies']->first();
            $this->assertIsInt($entry['enemy_id']);
            $this->assertIsInt($entry['kill_zone_id']);
        } finally {
            LiveSessionOverpulledEnemy::query()->where('live_session_id', $liveSession->id)->delete();
            OverpulledEnemy::query()->where('live_session_id', $liveSession->id)->delete();
            $liveSession->delete();
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function toArray_givenInCombatEnemy_includesResolvedEnemyIdInInCombatEnemies(): void
    {
        // Arrange
        $dungeonRoute = $this->createNonFacadeDungeonRouteWithEnemies();

        /** @var Enemy|null $enemy */
        $enemy = Enemy::query()
            ->where('mapping_version_id', $dungeonRoute->mapping_version_id)
            ->whereNotNull('npc_id')
            ->whereNotNull('mdt_id')
            ->first();

        if ($enemy === null) {
            $this->markTestSkipped('No enemy with npc_id+mdt_id found for this mapping version.');
        }

        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create(['dungeon_route_id' => $dungeonRoute->id]);

        try {
            LiveSessionEnemyState::query()->create([
                'live_session_id' => $liveSession->id,
                'state'           => LiveSessionEnemyStateType::InCombat,
                'npc_id'          => $enemy->npc_id,
                'mdt_id'          => $enemy->mdt_id,
            ]);

            // Act
            $array = $this->makeContext($liveSession)->toArray();

            // Assert
            $this->assertArrayHasKey('inCombatEnemies', $array);
            $this->assertContains($enemy->id, $array['inCombatEnemies']->all());
        } finally {
            LiveSessionEnemyState::query()->where('live_session_id', $liveSession->id)->delete();
            $liveSession->delete();
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function toArray_givenPlayerPosition_includesPositionInPlayerPositions(): void
    {
        // Arrange
        $dungeonRoute = $this->createNonFacadeDungeonRouteWithEnemies();

        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create(['dungeon_route_id' => $dungeonRoute->id]);

        try {
            LiveSessionPlayerPosition::query()->create([
                'live_session_id'   => $liveSession->id,
                'player_guid'       => 'Player-9999-TESTTEST',
                'character_name'    => 'Herochar',
                'lat'               => -150.5,
                'lng'               => 75.3,
                'floor_id'          => 1,
                'class_id'          => 7,
                'specialization_id' => 262,
                'updated_at'        => now(),
            ]);

            // Act
            $array = $this->makeContext($liveSession)->toArray();

            // Assert
            $this->assertArrayHasKey('playerPositions', $array);
            $this->assertCount(1, $array['playerPositions']);

            $position = $array['playerPositions']->first();
            $this->assertSame('Player-9999-TESTTEST', $position['player_guid']);
            $this->assertSame('Herochar', $position['character_name']);
            $this->assertSame(1, $position['floor_id']);
            $this->assertSame(7, $position['class_id']);
            $this->assertSame(262, $position['specialization_id']);

            // The appended icon URL is resolved from the specialization id
            $serialized = $position->toArray();
            $this->assertArrayHasKey('specialization_icon_url', $serialized);
            $this->assertNotNull($serialized['specialization_icon_url']);
        } finally {
            LiveSessionPlayerPosition::query()->where('live_session_id', $liveSession->id)->delete();
            $liveSession->delete();
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function toArray_givenEmptyCombatState_includesEmptyCollections(): void
    {
        // Arrange
        /** @var DungeonRoute $dungeonRoute */
        $dungeonRoute = $this->createNonFacadeDungeonRouteWithEnemies();

        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create(['dungeon_route_id' => $dungeonRoute->id]);

        try {
            // Act
            $array = $this->makeContext($liveSession)->toArray();

            // Assert
            $this->assertArrayHasKey('killedEnemies', $array);
            $this->assertArrayHasKey('inCombatEnemies', $array);
            $this->assertArrayHasKey('playerPositions', $array);
            $this->assertTrue($array['killedEnemies']->isEmpty());
            $this->assertTrue($array['inCombatEnemies']->isEmpty());
            $this->assertTrue($array['playerPositions']->isEmpty());
        } finally {
            $liveSession->delete();
            $dungeonRoute->delete();
        }
    }
}
