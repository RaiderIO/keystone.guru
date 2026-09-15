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
     *
     * Both unresolvable npcs are given enemy forces so the coordinate skip is what is under test here
     * rather than the "not worth any enemy forces" skip.
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

        $dungeonRoute      = null;
        $npcEnemyForcesIds = [];

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

            foreach ([-1, -2] as $unresolvedNpcId) {
                $npcEnemyForcesIds[] = NpcEnemyForces::query()->create([
                    'mapping_version_id' => $zeroedEnemy->mapping_version_id,
                    'npc_id'             => $unresolvedNpcId,
                    'enemy_forces'       => 10,
                ])->id;
            }

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

            if ($npcEnemyForcesIds !== []) {
                NpcEnemyForces::query()->whereKey($npcEnemyForcesIds)->delete();
                new NpcEnemyForces()->flushCache();
            }
        }
    }

    /**
     * An unresolved npc that is not worth any enemy forces in the mapping version never affects the built route, so it
     * must not be recorded. "Not worth any enemy forces" covers all three shapes: an explicit 0 row, no enemy forces row
     * at all while the npc IS mapped (#4475 - by far the common encoding), and an npc the mapping does not know at all
     * (a temporary add spawned mid-fight). Only an npc actually worth enemy forces is recorded.
     */
    #[Test]
    public function saveCombatLogRouteEnemyFailures_givenUnresolvedNpcsWithoutEnemyForces_recordsOnlyTheNpcWorthEnemyForces(): void
    {
        $resolvedEnemy = Enemy::query()->whereNotNull('floor_id')->with('floor')->first();
        $this->assertNotNull($resolvedEnemy, 'Expected at least one seeded Enemy with a floor.');

        $zeroForcesNpcId       = 99930;
        $noForcesRowNpcId      = 99931;
        $unknownToMappingNpcId = 99932;
        $worthForcesNpcId      = 99933;
        $dungeonRoute          = null;
        $npcEnemyForcesIds     = [];
        $mappedNpcIds          = [$noForcesRowNpcId, $worthForcesNpcId];
        $enemyIds              = [];

        try {
            // Arrange
            $dungeonRoute = DungeonRoute::factory()->create([
                'dungeon_id'         => $resolvedEnemy->floor->dungeon_id,
                'mapping_version_id' => $resolvedEnemy->mapping_version_id,
            ]);

            foreach ([$zeroForcesNpcId => 0, $worthForcesNpcId => 10] as $npcId => $enemyForces) {
                $npcEnemyForcesIds[] = NpcEnemyForces::query()->create([
                    'mapping_version_id' => $resolvedEnemy->mapping_version_id,
                    'npc_id'             => $npcId,
                    'enemy_forces'       => $enemyForces,
                ])->id;
            }

            // Being present in the mapping must not by itself make a 0-forces npc worth recording
            foreach ($mappedNpcIds as $mappedNpcId) {
                $enemyIds[] = Enemy::query()->create([
                    'mapping_version_id' => $resolvedEnemy->mapping_version_id,
                    'floor_id'           => $resolvedEnemy->floor_id,
                    'npc_id'             => $mappedNpcId,
                    'lat'                => $resolvedEnemy->lat,
                    'lng'                => $resolvedEnemy->lng,
                ])->id;
            }

            // npc1 resolves, establishing the floor for the unresolved npcs after it
            $resolved = new CombatLogRouteNpcRequestDto(npcId: $resolvedEnemy->npc_id, coord: new CombatLogRouteCoordRequestDto(1.0, 1.0));
            $resolved->setResolvedEnemy($resolvedEnemy);

            $combatLogRoute = new CombatLogRouteRequestDto(npcs: new Collection([
                $resolved,
                new CombatLogRouteNpcRequestDto(npcId: $zeroForcesNpcId, coord: new CombatLogRouteCoordRequestDto(2.0, 2.0)),
                new CombatLogRouteNpcRequestDto(npcId: $noForcesRowNpcId, coord: new CombatLogRouteCoordRequestDto(3.0, 3.0)),
                new CombatLogRouteNpcRequestDto(npcId: $unknownToMappingNpcId, coord: new CombatLogRouteCoordRequestDto(4.0, 4.0)),
                new CombatLogRouteNpcRequestDto(npcId: $worthForcesNpcId, coord: new CombatLogRouteCoordRequestDto(5.0, 5.0)),
            ]));

            /** @var CombatLogRouteDungeonRouteServiceInterface $service */
            $service = app(CombatLogRouteDungeonRouteServiceInterface::class);
            $method  = new ReflectionClass($service)->getMethod('saveCombatLogRouteEnemyFailures');

            // Act
            $method->invokeArgs($service, [$dungeonRoute->mappingVersion, $combatLogRoute, $dungeonRoute]);

            // Assert
            $failures = CombatLogRouteEnemyFailure::where('dungeon_route_id', $dungeonRoute->id)->get();
            $this->assertCount(1, $failures);
            $this->assertSame($worthForcesNpcId, $failures->first()->npc_id);
        } finally {
            if ($dungeonRoute !== null) {
                CombatLogRouteEnemyFailure::where('dungeon_route_id', $dungeonRoute->id)->delete();
                $dungeonRoute->delete();
            }

            if ($enemyIds !== []) {
                Enemy::query()->whereKey($enemyIds)->delete();
                new Enemy()->flushCache();
            }

            if ($npcEnemyForcesIds !== []) {
                NpcEnemyForces::query()->whereKey($npcEnemyForcesIds)->delete();
                new NpcEnemyForces()->flushCache();
            }
        }
    }
}
