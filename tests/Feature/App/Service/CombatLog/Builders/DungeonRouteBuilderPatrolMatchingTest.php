<?php

namespace Tests\Feature\App\Service\CombatLog\Builders;

use App\Logic\Structs\IngameXY;
use App\Models\Enemy;
use App\Models\EnemyPatrol;
use App\Models\Floor\Floor;
use App\Models\Polyline;
use App\Service\CombatLog\Builders\CombatLogRouteDungeonRouteBuilder;
use App\Service\CombatLog\Builders\DungeonRouteBuilder;
use App\Service\CombatLog\Builders\Logging\CombatLogRouteDungeonRouteBuilderLoggingInterface;
use App\Service\CombatLog\Models\ActivePull\ActivePullEnemy;
use App\Service\CombatLog\Models\ClosestEnemy;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Tests\TestCases\PublicTestCase;

/**
 * Drives the Auto Route Creator's patrol strategy - findClosestEnemyAndDistanceFromList() with $considerPatrols - on
 * unsaved enemies on a synthetic floor, so the only geometry in play is the patrol paths the test lays out.
 */
#[Group('CombatLog')]
#[Group('DungeonRouteBuilder')]
final class DungeonRouteBuilderPatrolMatchingTest extends PublicTestCase
{
    private const float ENGAGEMENT_MAX_RANGE = 150.0;

    #[Test]
    public function findClosestEnemyAndDistanceFromList_givenEngagementMidwayAlongALongPatrolSegment_matchesThatPatrol(): void
    {
        // Arrange - the long patrol's vertices are 400 yards from the engagement, but its segment passes 20 yards from
        // it. The short patrol's only vertex is 100 yards away, closer than either of the long patrol's vertices.
        $floor          = $this->createFloor();
        $longPatroller  = $this->createPatrollingEnemy($floor, 1, [[100, 500], [900, 500]]);
        $shortPatroller = $this->createPatrollingEnemy($floor, 2, [[500, 620]]);
        $closestEnemy   = new ClosestEnemy();

        // Act
        $this->findClosestEnemyAndDistanceFromList(
            $floor,
            collect([$shortPatroller, $longPatroller]),
            new ActivePullEnemy('Creature-0-1', 1, 500, 520, Carbon::now(), null),
            $closestEnemy,
        );

        // Assert
        $this->assertSame($longPatroller, $closestEnemy->getEnemy());
        $this->assertEqualsWithDelta(20, $closestEnemy->getDistanceBetweenEnemies(), 0.001);
    }

    #[Test]
    public function findClosestEnemyAndDistanceFromList_givenEngagementOutOfRangeOfEveryPatrolSegment_matchesNothing(): void
    {
        // Arrange
        $floor        = $this->createFloor();
        $patroller    = $this->createPatrollingEnemy($floor, 1, [[100, 500], [900, 500]]);
        $closestEnemy = new ClosestEnemy();

        // Act
        $this->findClosestEnemyAndDistanceFromList(
            $floor,
            collect([$patroller]),
            new ActivePullEnemy('Creature-0-1', 1, 500, 500 + self::ENGAGEMENT_MAX_RANGE + 1, Carbon::now(), null),
            $closestEnemy,
        );

        // Assert
        $this->assertNull($closestEnemy->getEnemy());
    }

    private function createFloor(): Floor
    {
        return new Floor()->forceFill([
            'id'                         => 1,
            'facade'                     => false,
            'ingame_min_x'               => 0,
            'ingame_max_x'               => 1000,
            'ingame_min_y'               => 0,
            'ingame_max_y'               => 1000,
            'enemy_engagement_max_range' => self::ENGAGEMENT_MAX_RANGE,
        ]);
    }

    /**
     * @param list<array{0: float, 1: float}> $ingameVertices
     */
    private function createPatrollingEnemy(Floor $floor, int $id, array $ingameVertices): Enemy
    {
        $coordinatesService = app(CoordinatesServiceInterface::class);

        $vertices = [];
        foreach ($ingameVertices as [$x, $y]) {
            $latLng     = $coordinatesService->calculateMapLocationForIngameLocation(new IngameXY($x, $y, $floor));
            $vertices[] = ['lat' => $latLng->getLat(), 'lng' => $latLng->getLng()];
        }

        $enemyPatrol = new EnemyPatrol()->setRelation(
            'polyline',
            new Polyline()->forceFill(['vertices_json' => json_encode($vertices)]),
        );

        return new Enemy()
            ->forceFill(['id' => $id, 'kill_priority' => 0, 'lat' => $vertices[0]['lat'], 'lng' => $vertices[0]['lng']])
            ->setRelation('floor', $floor)
            ->setRelation('enemyPatrol', $enemyPatrol);
    }

    /**
     * @param Collection<int, Enemy> $enemies
     */
    private function findClosestEnemyAndDistanceFromList(
        Floor           $floor,
        Collection      $enemies,
        ActivePullEnemy $activePullEnemy,
        ClosestEnemy    $closestEnemy,
    ): void {
        $builder = new ReflectionClass(CombatLogRouteDungeonRouteBuilder::class)->newInstanceWithoutConstructor();

        new ReflectionProperty(DungeonRouteBuilder::class, 'coordinatesService')
            ->setValue($builder, app(CoordinatesServiceInterface::class));
        new ReflectionProperty(DungeonRouteBuilder::class, 'log')
            ->setValue($builder, app(CombatLogRouteDungeonRouteBuilderLoggingInterface::class));
        new ReflectionProperty(DungeonRouteBuilder::class, 'currentFloor')
            ->setValue($builder, $floor);

        new ReflectionMethod(DungeonRouteBuilder::class, 'findClosestEnemyAndDistanceFromList')
            ->invoke($builder, $enemies, $activePullEnemy, $closestEnemy, true);
    }
}
