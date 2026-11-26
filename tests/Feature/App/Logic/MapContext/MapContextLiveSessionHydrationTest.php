<?php

namespace Tests\Feature\App\Logic\MapContext;

use App\Logic\MapContext\Map\MapContextLiveSession;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\LiveSession\LiveSession;
use App\Models\LiveSession\LiveSessionEnemyState;
use App\Models\LiveSession\LiveSessionEnemyStateType;
use App\Models\LiveSession\LiveSessionPlayerPosition;
use App\Models\User;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\KillZonePath\KillZonePathServiceInterface;
use App\Service\LiveSession\LiveSessionStateServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\GeneratesDungeonRoutes;
use Tests\TestCases\PublicTestCase;

#[Group('MapContext')]
#[Group('MapContextLiveSession')]
final class MapContextLiveSessionHydrationTest extends PublicTestCase
{
    use GeneratesDungeonRoutes;

    private function makeContext(LiveSession $liveSession, ?LiveSessionStateServiceInterface $liveSessionStateService = null): MapContextLiveSession
    {
        return new MapContextLiveSession(
            app(CacheServiceInterface::class),
            app(CoordinatesServiceInterface::class),
            app(KillZonePathServiceInterface::class),
            $liveSessionStateService ?? app(LiveSessionStateServiceInterface::class),
            $liveSession,
            User::MAP_FACADE_STYLE_SPLIT_FLOORS,
        );
    }

    #[Test]
    public function toArray_givenALiveSession_carriesTheStateOfThatSession(): void
    {
        // Arrange
        $dungeonRoute = $this->createNonFacadeDungeonRouteWithEnemies();

        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create(['dungeon_route_id' => $dungeonRoute->id]);

        $state = [
            'killed'     => [100, 2],
            'in_combat'  => [102],
            'obsolete'   => [],
            'overpulled' => [[103, 4242]],
            'players'    => [],
        ];
        $liveSessionStateService = $this->createMock(LiveSessionStateServiceInterface::class);
        $liveSessionStateService->expects($this->once())
            ->method('getState')
            ->with($this->callback(static fn(LiveSession $argument): bool => $argument->id === $liveSession->id))
            ->willReturn($state);

        try {
            // Act
            $array = $this->makeContext($liveSession, $liveSessionStateService)->toArray();

            // Assert
            $this->assertSame($state, $array['liveSessionState']);
            $this->assertSame($liveSession->public_key, $array['liveSessionPublicKey']);
        } finally {
            $liveSession->delete();
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function toArray_givenKilledEnemy_includesResolvedEnemyIdInTheStatesKilledEnemies(): void
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
            $this->assertContains($enemy->id, array_map(
                static fn(int $index): int => array_sum(array_slice($array['liveSessionState']['killed'], 0, $index + 1)),
                array_keys($array['liveSessionState']['killed']),
            ));
        } finally {
            LiveSessionEnemyState::query()->where('live_session_id', $liveSession->id)->delete();
            $liveSession->delete();
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function toArray_givenPlayerPosition_includesThePlayerInTheStatesPlayers(): void
    {
        // Arrange
        $dungeonRoute = $this->createNonFacadeDungeonRouteWithEnemies();

        $floor = $dungeonRoute->dungeon->floors()->firstOrFail();

        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create(['dungeon_route_id' => $dungeonRoute->id]);

        try {
            LiveSessionPlayerPosition::query()->create([
                'live_session_id'   => $liveSession->id,
                'player_guid'       => 'Player-9999-TESTTEST',
                'character_name'    => 'Herochar',
                'lat'               => -150.5,
                'lng'               => 75.3,
                'floor_id'          => $floor->id,
                'class_id'          => 7,
                'specialization_id' => 262,
                'updated_at'        => now(),
            ]);

            // Act
            $array = $this->makeContext($liveSession)->toArray();

            // Assert
            $this->assertCount(1, $array['liveSessionState']['players']);

            $player = $array['liveSessionState']['players'][0];
            $this->assertSame('Player-9999-TESTTEST', $player['guid']);
            $this->assertSame('Herochar', $player['name']);
            $this->assertSame([-150.5, 75.3, $floor->id], $player['split_floors']);
            $this->assertSame(7, $player['class_id']);
            $this->assertSame(262, $player['specialization_id']);
            $this->assertNotNull($player['specialization_icon_url']);
        } finally {
            LiveSessionPlayerPosition::query()->where('live_session_id', $liveSession->id)->delete();
            $liveSession->delete();
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function toArray_givenEmptyCombatState_includesAnEmptyState(): void
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
            $this->assertSame([
                'killed'     => [],
                'in_combat'  => [],
                'obsolete'   => [],
                'overpulled' => [],
                'players'    => [],
            ], $array['liveSessionState']);
        } finally {
            $liveSession->delete();
            $dungeonRoute->delete();
        }
    }
}
