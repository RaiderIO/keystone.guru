<?php

namespace Tests\Feature\App\Service\CombatLog;

use App\Logic\Structs\IngameXY;
use App\Models\CombatLog\CombatLogRouteEnemyFailure;
use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\NpcEnemyForces;
use App\Service\CombatLog\CombatLogRouteEnemyFailureAnalysisServiceInterface;
use App\Service\CombatLog\Dtos\EnemyFailureAnalysis\EnemyFailureCluster;
use App\Service\CombatLog\Dtos\EnemyFailureAnalysis\EnemyFailureVerdict;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Database\Eloquent\Builder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

/**
 * Every test works on its own MappingVersion (no enemies unless the test creates them) of a multi-floor dungeon, with
 * failures placed by ingame coordinates so distances are what the test says they are.
 */
#[Group('CombatLog')]
#[Group('CombatLogRouteEnemyFailureAnalysisService')]
final class CombatLogRouteEnemyFailureAnalysisServiceTest extends PublicTestCase
{
    use ProvidesDungeon;

    private const int NPC_ID = 99950;

    private CombatLogRouteEnemyFailureAnalysisServiceInterface $service;

    private CoordinatesServiceInterface $coordinatesService;

    private Dungeon $dungeon;

    private MappingVersion $mappingVersion;

    private Floor $floor;

    private Floor $otherFloor;

    /** @var array<int, int> */
    private array $createdFailureIds = [];

    /** @var array<int, int> */
    private array $createdEnemyForcesIds = [];

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->service            = app(CombatLogRouteEnemyFailureAnalysisServiceInterface::class);
        $this->coordinatesService = app(CoordinatesServiceInterface::class);

        // Two real (non-facade) floors with ingame coordinates so conversions work on both
        [$this->dungeon] = $this->findDungeon(facadeEnabled: false, minActiveFloors: 2, constraint: static function (Builder $query): void {
            $query->whereHas('floors', static fn(Builder $floors) => $floors->where('facade', 0)->where('ingame_max_x', '!=', 0), '>=', 2);
        });

        /** @var Floor[] $floors */
        $floors           = $this->dungeon->floors()->where('facade', 0)->where('ingame_max_x', '!=', 0)->orderBy('index')->limit(2)->get()->all();
        $this->floor      = $floors[0];
        $this->otherFloor = $floors[1];

        $current              = $this->dungeon->getCurrentMappingVersion();
        $this->mappingVersion = MappingVersion::findOrFail(MappingVersion::insertGetId([
            'game_version_id'                 => $current->game_version_id,
            'dungeon_id'                      => $this->dungeon->id,
            'version'                         => $current->version + 100,
            'enemy_forces_required'           => $current->enemy_forces_required,
            'enemy_forces_required_teeming'   => $current->enemy_forces_required_teeming,
            'enemy_forces_shrouded'           => $current->enemy_forces_shrouded,
            'enemy_forces_shrouded_zul_gamux' => $current->enemy_forces_shrouded_zul_gamux,
            'timer_max_seconds'               => $current->timer_max_seconds,
            'created_at'                      => now(),
            'updated_at'                      => now(),
        ]));
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            CombatLogRouteEnemyFailure::query()->whereIn('id', $this->createdFailureIds)->delete();
            NpcEnemyForces::query()->whereIn('id', $this->createdEnemyForcesIds)->delete();
            // Enemy and MappingVersion are SeederModels - delete() is refused, use the query builder
            Enemy::query()->where('mapping_version_id', $this->mappingVersion->id)->delete();
            MappingVersion::query()->whereKey($this->mappingVersion->id)->delete();
            new Enemy()->flushCache();
            new NpcEnemyForces()->flushCache();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function analyze_givenNoEnemiesForNpc_returnsNpcNotMapped(): void
    {
        // Arrange
        $this->createFailuresAround($this->floor, 1000, 1000, 6);

        // Act
        $clusters = $this->analyze();

        // Assert
        $this->assertCount(1, $clusters);
        $this->assertSame(EnemyFailureVerdict::NpcNotMapped, $clusters[0]->verdict);
        $this->assertSame(6, $clusters[0]->count);
        $this->assertNull($clusters[0]->nearestEnemyId);
    }

    #[Test]
    public function analyze_givenEnemyFartherThanRange_returnsNoEnemyInRange(): void
    {
        // Arrange — the only enemy of the npc is 400 yd away on the same floor
        $this->createEnemy($this->floor, 1400, 1000);
        $this->createFailuresAround($this->floor, 1000, 1000, 6);

        // Act
        $clusters = $this->analyze();

        // Assert
        $this->assertCount(1, $clusters);
        $this->assertSame(EnemyFailureVerdict::NoEnemyInRange, $clusters[0]->verdict);
        $this->assertNotNull($clusters[0]->nearestEnemyId);
        $this->assertEqualsWithDelta(400, $clusters[0]->nearestEnemyDistance, 5);
        $this->assertSame(0, $clusters[0]->enemiesWithinRange);
    }

    #[Test]
    public function analyze_givenEnemyWithinRangeOnSameFloor_returnsEnemiesExhausted(): void
    {
        // Arrange — an enemy 30 yd from the failures
        $enemy = $this->createEnemy($this->floor, 1030, 1000);
        $this->createFailuresAround($this->floor, 1000, 1000, 6);

        // Act
        $clusters = $this->analyze();

        // Assert
        $this->assertCount(1, $clusters);
        $this->assertSame(EnemyFailureVerdict::EnemiesExhausted, $clusters[0]->verdict);
        $this->assertSame($enemy->id, $clusters[0]->nearestEnemyId);
        $this->assertSame(1, $clusters[0]->enemiesWithinRange);
    }

    #[Test]
    public function analyze_givenEnemyWithinRangeOnlyOnOtherFloor_returnsWrongFloorArtifact(): void
    {
        // Arrange — same ingame position, but the enemy sits on the other floor
        $this->createEnemy($this->otherFloor, 1010, 1000);
        $this->createFailuresAround($this->floor, 1000, 1000, 6);

        // Act
        $clusters = $this->analyze();

        // Assert
        $this->assertCount(1, $clusters);
        $this->assertSame(EnemyFailureVerdict::WrongFloorArtifact, $clusters[0]->verdict);
        $this->assertSame($this->otherFloor->id, $clusters[0]->nearestEnemyFloorId);
        $this->assertSame(0, $clusters[0]->enemiesWithinRange);
    }

    #[Test]
    public function analyze_givenPointsWithinRadius_mergesIntoOneCluster(): void
    {
        // Arrange — two groups 20 yd apart, well inside the 40 yd radius
        $this->createFailuresAround($this->floor, 1000, 1000, 3);
        $this->createFailuresAround($this->floor, 1020, 1000, 3);

        // Act
        $clusters = $this->analyze();

        // Assert
        $this->assertCount(1, $clusters);
        $this->assertSame(6, $clusters[0]->count);
    }

    #[Test]
    public function analyze_givenPointsBeyondRadius_returnsSeparateClusters(): void
    {
        // Arrange — two groups 300 yd apart
        $this->createFailuresAround($this->floor, 1000, 1000, 3);
        $this->createFailuresAround($this->floor, 1300, 1000, 3);

        // Act
        $clusters = $this->analyze();

        // Assert
        $this->assertCount(2, $clusters);
        $this->assertSame([3, 3], array_map(static fn(EnemyFailureCluster $cluster) => $cluster->count, $clusters));
    }

    #[Test]
    public function analyze_givenFewerRowsThanMinCount_flagsLowVolumeAndSortsItLast(): void
    {
        // Arrange — a big cluster and a tiny one far away
        $this->createFailuresAround($this->floor, 1000, 1000, 8);
        $this->createFailuresAround($this->floor, 1500, 1000, 2);

        // Act
        $clusters = $this->analyze(minCount: 5);

        // Assert
        $this->assertCount(2, $clusters);
        $this->assertFalse($clusters[0]->lowVolume);
        $this->assertSame(8, $clusters[0]->count);
        $this->assertTrue($clusters[1]->lowVolume);
        $this->assertSame(2, $clusters[1]->count);
    }

    #[Test]
    public function analyze_givenThreeOrMoreSpreadPoints_returnsHullPolygon(): void
    {
        // Arrange — a triangle of failures
        $this->createFailure($this->floor, 1000, 1000);
        $this->createFailure($this->floor, 1020, 1000);
        $this->createFailure($this->floor, 1010, 1020);

        // Act
        $clusters = $this->analyze();

        // Assert
        $this->assertCount(1, $clusters);
        $this->assertCount(3, $clusters[0]->hull);
        $this->assertSame($this->floor->id, $clusters[0]->hull[0]->getFloor()?->id);
    }

    /**
     * Both shapes of "not worth any enemy forces" are excluded: an explicit 0 row, and no row at all while some other
     * npc in the mapping version does have enemy forces (#4475). Only the latter npc's failures are worth triaging.
     */
    #[Test]
    #[DataProvider('enemyForcesProvider')]
    public function analyze_givenNpcNotWorthAnyEnemyForces_excludesIt(?int $enemyForces): void
    {
        // Arrange - another npc carries enemy forces, so the mapping version counts as tuned
        $this->createEnemyForces(self::NPC_ID + 1, 10);

        if ($enemyForces !== null) {
            $this->createEnemyForces(self::NPC_ID, $enemyForces);
        }

        $this->createFailuresAround($this->floor, 1000, 1000, 6);

        // Act
        $clusters = $this->analyze();

        // Assert
        $this->assertCount(0, $clusters);
    }

    /**
     * @return array<string, array{0: int|null}>
     */
    public static function enemyForcesProvider(): array
    {
        return [
            'explicit 0 enemy forces' => [0],
            'no enemy forces row'     => [null],
        ];
    }

    /**
     * A mapping version nobody has tuned enemy forces on yet must not have every failure filtered away - there is
     * nothing to tell noise from a real gap with, so everything stays visible.
     */
    #[Test]
    public function analyze_givenMappingVersionWithoutAnyEnemyForces_keepsEveryFailure(): void
    {
        // Arrange
        $this->createFailuresAround($this->floor, 1000, 1000, 6);

        // Act
        $clusters = $this->analyze();

        // Assert
        $this->assertCount(1, $clusters);
        $this->assertSame(6, $clusters[0]->count);
    }

    #[Test]
    public function analyze_givenNpcWorthEnemyForces_keepsIt(): void
    {
        // Arrange
        $this->createEnemyForces(self::NPC_ID, 10);
        $this->createFailuresAround($this->floor, 1000, 1000, 6);

        // Act
        $clusters = $this->analyze();

        // Assert
        $this->assertCount(1, $clusters);
        $this->assertSame(6, $clusters[0]->count);
    }

    private function createEnemyForces(int $npcId, int $enemyForces): void
    {
        $this->createdEnemyForcesIds[] = NpcEnemyForces::query()->create([
            'mapping_version_id' => $this->mappingVersion->id,
            'npc_id'             => $npcId,
            'enemy_forces'       => $enemyForces,
        ])->id;
    }

    #[Test]
    public function analyze_givenRoutes_countsDistinctRoutesAndAverage(): void
    {
        // Arrange — 6 failures over 3 routes (2 each)
        foreach ([1001, 1001, 1002, 1002, 1003, 1003] as $routeId) {
            $this->createFailure($this->floor, 1000, 1000, $routeId);
        }

        // Act
        $clusters = $this->analyze();

        // Assert
        $this->assertSame(3, $clusters[0]->routeCount);
        $this->assertEqualsWithDelta(2.0, $clusters[0]->avgFailuresPerRoute, 0.001);
    }

    /**
     * @return EnemyFailureCluster[]
     */
    private function analyze(?int $minCount = null): array
    {
        return $this->service->analyze($this->dungeon, $this->mappingVersion, [self::NPC_ID], $minCount)->clusters;
    }

    private function createEnemy(Floor $floor, float $x, float $y): Enemy
    {
        $latLng = $this->coordinatesService->calculateMapLocationForIngameLocation(new IngameXY($x, $y, $floor));

        return Enemy::create([
            'mapping_version_id' => $this->mappingVersion->id,
            'floor_id'           => $floor->id,
            'npc_id'             => self::NPC_ID,
            'teeming'            => null,
            'required'           => true,
            'lat'                => $latLng->getLat(),
            'lng'                => $latLng->getLng(),
        ]);
    }

    private function createFailure(Floor $floor, float $x, float $y, ?int $routeId = null): CombatLogRouteEnemyFailure
    {
        $latLng = $this->coordinatesService->calculateMapLocationForIngameLocation(new IngameXY($x, $y, $floor));

        $failure = CombatLogRouteEnemyFailure::create([
            'dungeon_route_id'   => $routeId,
            'dungeon_id'         => $this->dungeon->id,
            'floor_id'           => $floor->id,
            'mapping_version_id' => $this->mappingVersion->id,
            'npc_id'             => self::NPC_ID,
            'lat'                => $latLng->getLat(),
            'lng'                => $latLng->getLng(),
        ]);

        $this->createdFailureIds[] = $failure->id;

        return $failure;
    }

    /**
     * $count failures scattered within a few yards of (x, y), each on its own route.
     */
    private function createFailuresAround(Floor $floor, float $x, float $y, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $this->createFailure($floor, $x + ($i % 3) * 2, $y + intdiv($i, 3) * 2, 5000 + $i);
        }
    }
}
