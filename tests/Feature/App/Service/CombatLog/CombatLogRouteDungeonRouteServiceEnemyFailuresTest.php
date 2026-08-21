<?php

namespace Tests\Feature\App\Service\CombatLog;

use App\Dto\Request\CombatLog\Route\CombatLogRouteCoordRequestDto;
use App\Dto\Request\CombatLog\Route\CombatLogRouteNpcRequestDto;
use App\Dto\Request\CombatLog\Route\CombatLogRouteRequestDto;
use App\Models\CombatLog\CombatLogRouteEnemyFailure;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\Floor\Floor;
use App\Models\Npc\NpcEnemyForces;
use App\Service\CombatLog\CombatLogRouteDungeonRouteServiceInterface;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Tests\TestCases\PublicTestCase;

/**
 * Exercises CombatLogRouteDungeonRouteService::saveCombatLogRouteEnemyFailures() directly via
 * reflection rather than through the full API/builder pipeline: within a single test, that pipeline
 * resolves floors through FloorRepositorySwoole/EnemyRepositorySwoole, which cache Floor/Enemy
 * instances for the lifetime of the test (Laravel's TestCase rebuilds the container per test via
 * refreshApplication(), but not between requests within one test), so mutating a floor's ingame
 * coordinates and re-submitting within the same test would not reliably be observed there. The
 * method under test only ever reads floors via plain Eloquent relations, so a direct call is both
 * faster and immune to that caching layer.
 */
#[Group('CombatLog')]
#[Group('CombatLogRouteDungeonRouteService')]
final class CombatLogRouteDungeonRouteServiceEnemyFailuresTest extends PublicTestCase
{
    /**
     * Guards #3904: a floor with unset (zero-size) ingame coordinates must not fail the whole
     * combat log route submission - CombatLogRouteEnemyFailure is diagnostic bookkeeping only, so a
     * failure that can't be located should be skipped instead of throwing. A second unresolvable
     * npc on a floor WITH coordinates proves this is a selective skip, not "nothing got recorded".
     */
    #[Test]
    public function saveCombatLogRouteEnemyFailures_givenUnresolvableNpcOnFloorWithoutIngameCoordinates_skipsOnlyThatOne(): void
    {
        $zeroedEnemy = Enemy::query()->whereNotNull('floor_id')->with('floor')->first();
        $this->assertNotNull($zeroedEnemy, 'Expected at least one seeded Enemy with a floor.');

        $goodEnemy = Enemy::query()->whereNotNull('floor_id')->where('floor_id', '!=', $zeroedEnemy->floor_id)->with('floor')->first();
        $this->assertNotNull($goodEnemy, 'Expected at least one seeded Enemy on a different floor.');

        $floor          = $zeroedEnemy->floor;
        $originalCoords = [
            'ingame_min_x' => $floor->ingame_min_x,
            'ingame_min_y' => $floor->ingame_min_y,
            'ingame_max_x' => $floor->ingame_max_x,
            'ingame_max_y' => $floor->ingame_max_y,
        ];

        $dungeonRoute = null;

        try {
            // Arrange - zero out one enemy's floor so calculateMapLocationForIngameLocation() throws
            // for it, while the other enemy's floor keeps its real (non-zero) coordinates.
            $floor->update([
                'ingame_min_x' => 0,
                'ingame_min_y' => 0,
                'ingame_max_x' => 0,
                'ingame_max_y' => 0,
            ]);
            $floor->refresh();

            $dungeonRoute = DungeonRoute::factory()->create([
                'dungeon_id'         => $floor->dungeon_id,
                'mapping_version_id' => $zeroedEnemy->mapping_version_id,
            ]);

            // npc1 resolves to a real enemy on the zeroed floor, becoming the "previous floor" for npc2
            $resolvedOnZeroedFloor = new CombatLogRouteNpcRequestDto(npcId: $zeroedEnemy->npc_id, coord: new CombatLogRouteCoordRequestDto(1.0, 1.0));
            $resolvedOnZeroedFloor->setResolvedEnemy($zeroedEnemy);

            // npc2 does not resolve to an enemy, so it falls back to npc1's floor (the zeroed one) - skipped
            $unresolvedOnZeroedFloor = new CombatLogRouteNpcRequestDto(npcId: -1, coord: new CombatLogRouteCoordRequestDto(2.0, 2.0));

            // npc3 resolves to a real enemy on a floor with real coordinates, becoming the "previous floor" for npc4
            $resolvedOnGoodFloor = new CombatLogRouteNpcRequestDto(npcId: $goodEnemy->npc_id, coord: new CombatLogRouteCoordRequestDto(3.0, 3.0));
            $resolvedOnGoodFloor->setResolvedEnemy($goodEnemy);

            // npc4 does not resolve to an enemy, so it falls back to npc3's floor (real coordinates) - recorded
            $unresolvedOnGoodFloor = new CombatLogRouteNpcRequestDto(npcId: -2, coord: new CombatLogRouteCoordRequestDto(4.0, 4.0));

            $combatLogRoute = new CombatLogRouteRequestDto(npcs: new Collection([
                $resolvedOnZeroedFloor,
                $unresolvedOnZeroedFloor,
                $resolvedOnGoodFloor,
                $unresolvedOnGoodFloor,
            ]));

            /** @var CombatLogRouteDungeonRouteServiceInterface $service */
            $service = app(CombatLogRouteDungeonRouteServiceInterface::class);
            $method  = new ReflectionClass($service)->getMethod('saveCombatLogRouteEnemyFailures');

            // Act
            $method->invokeArgs($service, [$dungeonRoute->mappingVersion, $combatLogRoute, $dungeonRoute]);

            // Assert - no exception, exactly one failure recorded (npc4, on the floor with real coordinates)
            $failures = CombatLogRouteEnemyFailure::where('dungeon_route_id', $dungeonRoute->id)->get();
            $this->assertCount(1, $failures);
            $this->assertSame(-2, $failures->first()->npc_id);
            $this->assertSame($goodEnemy->floor_id, $failures->first()->floor_id);
        } finally {
            $floor->update($originalCoords);

            if ($dungeonRoute !== null) {
                CombatLogRouteEnemyFailure::where('dungeon_route_id', $dungeonRoute->id)->delete();
                $dungeonRoute->delete();
            }
        }
    }

    /**
     * An unresolved npc that is explicitly worth 0 enemy forces in the mapping version never affects the built route,
     * so it must not be recorded - while an unresolved npc WITHOUT any enemy forces row (the "not mapped" case) must.
     */
    #[Test]
    public function saveCombatLogRouteEnemyFailures_givenUnresolvedNpcWithZeroEnemyForces_recordsOnlyTheNpcWithoutEnemyForcesRow(): void
    {
        $resolvedEnemy = Enemy::query()->whereNotNull('floor_id')->with('floor')->first();
        $this->assertNotNull($resolvedEnemy, 'Expected at least one seeded Enemy with a floor.');

        $zeroForcesNpcId  = 99930;
        $unmappedNpcId    = 99931;
        $dungeonRoute     = null;
        $npcEnemyForcesId = null;

        try {
            // Arrange
            $dungeonRoute = DungeonRoute::factory()->create([
                'dungeon_id'         => $resolvedEnemy->floor->dungeon_id,
                'mapping_version_id' => $resolvedEnemy->mapping_version_id,
            ]);

            $npcEnemyForcesId = NpcEnemyForces::query()->create([
                'mapping_version_id' => $resolvedEnemy->mapping_version_id,
                'npc_id'             => $zeroForcesNpcId,
                'enemy_forces'       => 0,
            ])->id;

            // npc1 resolves, establishing the floor for the two unresolved npcs after it
            $resolved = new CombatLogRouteNpcRequestDto(npcId: $resolvedEnemy->npc_id, coord: new CombatLogRouteCoordRequestDto(1.0, 1.0));
            $resolved->setResolvedEnemy($resolvedEnemy);
            $unresolvedZeroForces = new CombatLogRouteNpcRequestDto(npcId: $zeroForcesNpcId, coord: new CombatLogRouteCoordRequestDto(2.0, 2.0));
            $unresolvedUnmapped   = new CombatLogRouteNpcRequestDto(npcId: $unmappedNpcId, coord: new CombatLogRouteCoordRequestDto(3.0, 3.0));

            $combatLogRoute = new CombatLogRouteRequestDto(npcs: new Collection([$resolved, $unresolvedZeroForces, $unresolvedUnmapped]));

            /** @var CombatLogRouteDungeonRouteServiceInterface $service */
            $service = app(CombatLogRouteDungeonRouteServiceInterface::class);
            $method  = new ReflectionClass($service)->getMethod('saveCombatLogRouteEnemyFailures');

            // Act
            $method->invokeArgs($service, [$dungeonRoute->mappingVersion, $combatLogRoute, $dungeonRoute]);

            // Assert
            $failures = CombatLogRouteEnemyFailure::where('dungeon_route_id', $dungeonRoute->id)->get();
            $this->assertCount(1, $failures);
            $this->assertSame($unmappedNpcId, $failures->first()->npc_id);
        } finally {
            if ($dungeonRoute !== null) {
                CombatLogRouteEnemyFailure::where('dungeon_route_id', $dungeonRoute->id)->delete();
                $dungeonRoute->delete();
            }

            if ($npcEnemyForcesId !== null) {
                NpcEnemyForces::query()->whereKey($npcEnemyForcesId)->delete();
                new NpcEnemyForces()->flushCache();
            }
        }
    }
}
