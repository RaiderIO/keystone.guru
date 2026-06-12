<?php

namespace Tests\Feature\App\Logic\MapContext;

use App\Logic\MapContext\Map\MapContextLiveSession;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\LiveSession\LiveSession;
use App\Models\LiveSession\LiveSessionKilledEnemy;
use App\Models\LiveSession\LiveSessionObsoleteEnemy;
use App\Models\LiveSession\LiveSessionPlayerPosition;
use App\Models\User;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\KillZonePath\KillZonePathServiceInterface;
use App\Service\LiveSession\LiveSessionCombatStateService;
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

    private function makeContext(LiveSession $liveSession): MapContextLiveSession
    {
        return new MapContextLiveSession(
            app(CacheServiceInterface::class),
            app(CoordinatesServiceInterface::class),
            app(KillZonePathServiceInterface::class),
            app(OverpulledEnemyServiceInterface::class),
            new LiveSessionCombatStateService(),
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
            LiveSessionKilledEnemy::query()->create([
                'live_session_id' => $liveSession->id,
                'npc_id'          => $enemy->npc_id,
                'mdt_id'          => $enemy->mdt_id,
            ]);

            // Act
            $array = $this->makeContext($liveSession)->toArray();

            // Assert
            $this->assertArrayHasKey('killedEnemies', $array);
            $this->assertContains($enemy->id, $array['killedEnemies']->all());
        } finally {
            LiveSessionKilledEnemy::query()->where('live_session_id', $liveSession->id)->delete();
            $liveSession->delete();
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function toArray_givenObsoleteEnemy_includesResolvedEnemyIdInObsoleteEnemies(): void
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
            LiveSessionObsoleteEnemy::query()->create([
                'live_session_id' => $liveSession->id,
                'npc_id'          => $enemy->npc_id,
                'mdt_id'          => $enemy->mdt_id,
            ]);

            // Act
            $array = $this->makeContext($liveSession)->toArray();

            // Assert
            $this->assertArrayHasKey('obsoleteEnemies', $array);
            $this->assertContains($enemy->id, $array['obsoleteEnemies']->all());
        } finally {
            LiveSessionObsoleteEnemy::query()->where('live_session_id', $liveSession->id)->delete();
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
                'live_session_id' => $liveSession->id,
                'player_guid'     => 'Player-9999-TESTTEST',
                'character_name'  => 'Herochar',
                'lat'             => -150.5,
                'lng'             => 75.3,
                'floor_id'        => 1,
                'updated_at'      => now(),
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
            $this->assertArrayHasKey('playerPositions', $array);
            $this->assertTrue($array['killedEnemies']->isEmpty());
            $this->assertTrue($array['playerPositions']->isEmpty());
        } finally {
            $liveSession->delete();
            $dungeonRoute->delete();
        }
    }
}
