<?php

namespace Tests\Feature\App\Service\CombatLog;

use App\Dto\Request\CombatLog\Route\CombatLogRouteCoordRequestDto;
use App\Dto\Request\CombatLog\Route\CombatLogRouteNpcRequestDto;
use App\Dto\Request\CombatLog\Route\CombatLogRouteRequestDto;
use App\Models\CombatLog\CombatLogRouteEnemyFailure;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\Floor\Floor;
use App\Service\CombatLog\CombatLogRouteDungeonRouteServiceInterface;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Tests\TestCases\PublicTestCase;

/**
 * Exercises CombatLogRouteDungeonRouteService::saveCombatLogRouteEnemyFailures() directly via
 * reflection rather than through the full API/builder pipeline: that pipeline resolves floors
 * through FloorRepositorySwoole/EnemyRepositorySwoole, which cache Floor/Enemy instances for the
 * lifetime of the process, so mutating a floor's ingame coordinates mid-test would not reliably be
 * observed there. The method under test only ever reads floors via plain Eloquent relations, so a
 * direct call is both faster and immune to that caching layer.
 */
#[Group('CombatLog')]
#[Group('CombatLogRouteDungeonRouteService')]
final class CombatLogRouteDungeonRouteServiceEnemyFailuresTest extends PublicTestCase
{
    /**
     * Guards #3904: a floor with unset (zero-size) ingame coordinates must not fail the whole
     * combat log route submission - CombatLogRouteEnemyFailure is diagnostic bookkeeping only, so a
     * failure that can't be located should be skipped instead of throwing.
     */
    #[Test]
    public function saveCombatLogRouteEnemyFailures_givenUnresolvableNpcOnFloorWithoutIngameCoordinates_skipsItWithoutThrowing(): void
    {
        $enemy = Enemy::query()->whereNotNull('floor_id')->with('floor')->first();
        $this->assertNotNull($enemy, 'Expected at least one seeded Enemy with a floor.');

        $floor          = $enemy->floor;
        $originalCoords = [
            'ingame_min_x' => $floor->ingame_min_x,
            'ingame_min_y' => $floor->ingame_min_y,
            'ingame_max_x' => $floor->ingame_max_x,
            'ingame_max_y' => $floor->ingame_max_y,
        ];

        $dungeonRoute = null;

        try {
            // Arrange - zero out the enemy's floor so calculateMapLocationForIngameLocation() throws
            $floor->update([
                'ingame_min_x' => 0,
                'ingame_min_y' => 0,
                'ingame_max_x' => 0,
                'ingame_max_y' => 0,
            ]);
            $floor->refresh();

            $dungeonRoute = DungeonRoute::factory()->create([
                'dungeon_id'         => $floor->dungeon_id,
                'mapping_version_id' => $enemy->mapping_version_id,
            ]);

            // npc1 resolves to a real enemy on the zeroed floor, becoming the "previous floor" for npc2
            $resolvedNpc = new CombatLogRouteNpcRequestDto(npcId: $enemy->npc_id, coord: new CombatLogRouteCoordRequestDto(1.0, 1.0));
            $resolvedNpc->setResolvedEnemy($enemy);

            // npc2 does not resolve to an enemy, so it falls back to npc1's floor (the zeroed one)
            $unresolvedNpc = new CombatLogRouteNpcRequestDto(npcId: -1, coord: new CombatLogRouteCoordRequestDto(2.0, 2.0));

            $combatLogRoute = new CombatLogRouteRequestDto(npcs: new Collection([$resolvedNpc, $unresolvedNpc]));

            $countBefore = CombatLogRouteEnemyFailure::where('dungeon_route_id', $dungeonRoute->id)->count();

            /** @var CombatLogRouteDungeonRouteServiceInterface $service */
            $service = app(CombatLogRouteDungeonRouteServiceInterface::class);
            $method  = new ReflectionClass($service)->getMethod('saveCombatLogRouteEnemyFailures');

            // Act
            $method->invokeArgs($service, [$dungeonRoute->mappingVersion, $combatLogRoute, $dungeonRoute]);

            // Assert - no exception, and no failure recorded for the floor that couldn't be located
            $this->assertSame($countBefore, CombatLogRouteEnemyFailure::where('dungeon_route_id', $dungeonRoute->id)->count());
        } finally {
            $floor->update($originalCoords);
            CombatLogRouteEnemyFailure::where('dungeon_route_id', $dungeonRoute?->id)->delete();
            $dungeonRoute?->delete();
        }
    }
}
