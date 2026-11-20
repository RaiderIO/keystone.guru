<?php

namespace Tests\Feature\App\Service\WalkGraph;

use App\Logic\Structs\IngameXY;
use App\Logic\WalkGraph\WalkGrid;
use App\Models\CombatLog\CombatLogWalkGraphContribution;
use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\EnemyPack;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Models\WalkGraph\EnemyPackWalkDistance;
use App\Models\WalkGraph\WalkGraph;
use App\Models\WalkGraph\WalkGraphSource;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\WalkGraph\WalkGraphServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('WalkGraph')]
final class WalkGraphServiceTest extends PublicTestCase
{
    use CreatesDungeon;

    private const int UI_MAP_ID = 9_990_501;

    private const float CELL_SIZE = 3.0;

    private WalkGraphServiceInterface $walkGraphService;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->walkGraphService = app(WalkGraphServiceInterface::class);
        config([
            'keystoneguru.walk_graph.cell_size' => self::CELL_SIZE,
            'keystoneguru.walk_graph.min_runs'  => 1,
        ]);

        $this->cleanUpTestData();
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            $this->cleanUpTestData();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function buildGraph_givenContributionsOfSeveralRuns_countsEachEdgeOncePerRun(): void
    {
        // Arrange - run 1 walked edge A in two of its files, and edge B; run 2 walked edge A
        $edgeA = WalkGrid::edgeKey(0, 0, 1, 0);
        $edgeB = WalkGrid::edgeKey(1, 0, 2, 0);
        $this->createContribution('run:1', [$edgeA, $edgeB]);
        $this->createContribution('run:1', [$edgeA]);
        $this->createContribution('run:2', [$edgeA]);

        // Act
        $walkGraph = $this->walkGraphService->buildGraph(self::UI_MAP_ID);

        // Assert
        $this->assertNotNull($walkGraph);
        $this->assertSame(2, $walkGraph->run_count);
        $this->assertSame(3, $walkGraph->node_count);
        $this->assertSame(2, $walkGraph->edge_count);
        $this->assertSame([$edgeA => 2, $edgeB => 1], WalkGrid::decodeEdgeCounts($walkGraph->edges));
        $this->assertSame(1, WalkGraph::query()->where('ui_map_id', self::UI_MAP_ID)->count());
    }

    #[Test]
    public function buildGraph_givenMoreRunsThanTheWindow_buildsFromTheNewestRunsAndPrunesTheOlderOnes(): void
    {
        // Arrange
        config(['keystoneguru.walk_graph.run_window' => 2]);
        $oldEdge    = WalkGrid::edgeKey(0, 0, 1, 0);
        $recentEdge = WalkGrid::edgeKey(5, 5, 6, 5);
        $oldest     = $this->createContribution('run:1', [$oldEdge]);
        $middle     = $this->createContribution('run:2', [$recentEdge]);
        $newest     = $this->createContribution('run:3', [$recentEdge]);

        // Act
        $walkGraph = $this->walkGraphService->buildGraph(self::UI_MAP_ID);

        // Assert
        $this->assertSame(2, $walkGraph->run_count);
        $this->assertSame([$recentEdge => 2], WalkGrid::decodeEdgeCounts($walkGraph->edges));
        $this->assertNull(CombatLogWalkGraphContribution::find($oldest->id));
        $this->assertNotNull(CombatLogWalkGraphContribution::find($middle->id));
        $this->assertNotNull(CombatLogWalkGraphContribution::find($newest->id));
    }

    #[Test]
    public function buildGraph_givenAnExistingGraph_replacesIt(): void
    {
        // Arrange
        $this->createContribution('run:1', [WalkGrid::edgeKey(0, 0, 1, 0)]);
        $this->walkGraphService->buildGraph(self::UI_MAP_ID);
        $this->createContribution('run:2', [WalkGrid::edgeKey(1, 0, 2, 0)]);

        // Act
        $walkGraph = $this->walkGraphService->buildGraph(self::UI_MAP_ID);

        // Assert
        $this->assertSame(2, $walkGraph->run_count);
        $this->assertSame(1, WalkGraph::query()->where('ui_map_id', self::UI_MAP_ID)->count());
    }

    #[Test]
    public function buildGraph_givenNoContributions_returnsNull(): void
    {
        // Arrange - nothing for this ui map

        // Act
        $walkGraph = $this->walkGraphService->buildGraph(self::UI_MAP_ID);

        // Assert
        $this->assertNull($walkGraph);
        $this->assertFalse(WalkGraph::query()->where('ui_map_id', self::UI_MAP_ID)->exists());
    }

    #[Test]
    public function buildGraph_givenSeededGraphAndTooFewRuns_keepsSeededGraph(): void
    {
        // Arrange
        config(['keystoneguru.walk_graph.min_runs' => 2]);
        $seededGraph = WalkGraph::factory()->seeded()->create(['ui_map_id' => self::UI_MAP_ID, 'run_count' => 40]);
        $this->createContribution('run:1', [WalkGrid::edgeKey(0, 0, 1, 0), WalkGrid::edgeKey(1, 0, 2, 0)]);

        // Act
        $walkGraph = $this->walkGraphService->buildGraph(self::UI_MAP_ID);

        // Assert
        $this->assertNull($walkGraph);
        $storedGraph = WalkGraph::query()->where('ui_map_id', self::UI_MAP_ID)->sole();
        $this->assertSame($seededGraph->id, $storedGraph->id);
        $this->assertSame(WalkGraphSource::Seeder, $storedGraph->source);
        $this->assertSame(40, $storedGraph->run_count);
        $this->assertSame($seededGraph->edges, $storedGraph->edges);
    }

    #[Test]
    public function buildGraph_givenSeededGraphAndEnoughRuns_replacesItWithCombatLogGraph(): void
    {
        // Arrange
        config(['keystoneguru.walk_graph.min_runs' => 2]);
        WalkGraph::factory()->seeded()->create(['ui_map_id' => self::UI_MAP_ID, 'run_count' => 40]);
        $edgeA = WalkGrid::edgeKey(5, 5, 6, 5);
        $this->createContribution('run:1', [$edgeA]);
        $this->createContribution('run:2', [$edgeA]);

        // Act
        $walkGraph = $this->walkGraphService->buildGraph(self::UI_MAP_ID);

        // Assert
        $this->assertNotNull($walkGraph);
        $storedGraph = WalkGraph::query()->where('ui_map_id', self::UI_MAP_ID)->sole();
        $this->assertSame(WalkGraphSource::CombatLog, $storedGraph->source);
        $this->assertSame(2, $storedGraph->run_count);
        $this->assertSame([$edgeA => 2], WalkGrid::decodeEdgeCounts($storedGraph->edges));
    }

    #[Test]
    public function buildGraph_givenCombatLogGraphAndTooFewRuns_replacesIt(): void
    {
        // Arrange
        config(['keystoneguru.walk_graph.min_runs' => 2]);
        WalkGraph::factory()->create(['ui_map_id' => self::UI_MAP_ID, 'run_count' => 40]);
        $edgeA = WalkGrid::edgeKey(5, 5, 6, 5);
        $this->createContribution('run:1', [$edgeA]);

        // Act
        $walkGraph = $this->walkGraphService->buildGraph(self::UI_MAP_ID);

        // Assert
        $this->assertNotNull($walkGraph);
        $storedGraph = WalkGraph::query()->where('ui_map_id', self::UI_MAP_ID)->sole();
        $this->assertSame(WalkGraphSource::CombatLog, $storedGraph->source);
        $this->assertSame(1, $storedGraph->run_count);
        $this->assertSame([$edgeA => 1], WalkGrid::decodeEdgeCounts($storedGraph->edges));
    }

    #[Test]
    public function calculatePackWalkDistances_givenWallBetweenPacks_writesTheWalkingDistanceAroundIt(): void
    {
        // Arrange - a U-shaped corridor from the first pack up, across and down to the second; the wall is between them
        [$floor, $mappingVersion, $baseCellX, $baseCellY] = $this->createFloor();
        $this->createWalkedPaths([
            [[0, 0], [0, 1], [0, 2], [0, 3], [0, 4], [1, 4], [2, 4], [2, 3], [2, 2], [2, 1], [2, 0]],
        ], $baseCellX, $baseCellY);
        $this->walkGraphService->buildGraph(self::UI_MAP_ID);
        $pack      = $this->createPackAtCell($mappingVersion, $floor, $baseCellX, $baseCellY);
        $otherPack = $this->createPackAtCell($mappingVersion, $floor, $baseCellX + 2, $baseCellY);

        // Act
        $count = $this->walkGraphService->calculatePackWalkDistances($mappingVersion, $floor);

        // Assert
        $this->assertSame(1, $count);
        $distance = EnemyPackWalkDistance::query()->where('mapping_version_id', $mappingVersion->id)->sole();
        $this->assertSame(min($pack->id, $otherPack->id), $distance->enemy_pack_id);
        $this->assertSame(max($pack->id, $otherPack->id), $distance->other_enemy_pack_id);
        $this->assertSame($floor->id, $distance->floor_id);
        $this->assertEqualsWithDelta(6.0, $distance->straight_distance, 0.05);
        $this->assertEqualsWithDelta(30.0, $distance->walk_distance, 0.05);
    }

    #[Test]
    public function calculatePackWalkDistances_givenPackNobodyWalkedNear_writesNoWalkingDistance(): void
    {
        // Arrange - the second pack is 30 yd from the nearest walked cell, outside the 15 yd snap radius
        [$floor, $mappingVersion, $baseCellX, $baseCellY] = $this->createFloor();
        $this->createWalkedPaths([[[0, 0], [1, 0], [2, 0]]], $baseCellX, $baseCellY);
        $this->walkGraphService->buildGraph(self::UI_MAP_ID);
        $this->createPackAtCell($mappingVersion, $floor, $baseCellX, $baseCellY);
        $this->createPackAtCell($mappingVersion, $floor, $baseCellX + 2, $baseCellY + 10);

        // Act
        $this->walkGraphService->calculatePackWalkDistances($mappingVersion, $floor);

        // Assert
        $distance = EnemyPackWalkDistance::query()->where('mapping_version_id', $mappingVersion->id)->sole();
        $this->assertEqualsWithDelta(hypot(6, 30), $distance->straight_distance, 0.05);
        $this->assertNull($distance->walk_distance);
    }

    #[Test]
    public function calculatePackWalkDistances_givenPacksFurtherApartThanTheMaximum_writesNoPair(): void
    {
        // Arrange
        config(['keystoneguru.walk_graph.max_pair_distance' => 5.0]);
        [$floor, $mappingVersion, $baseCellX, $baseCellY] = $this->createFloor();
        $this->createWalkedPaths([[[0, 0], [1, 0], [2, 0]]], $baseCellX, $baseCellY);
        $this->walkGraphService->buildGraph(self::UI_MAP_ID);
        $this->createPackAtCell($mappingVersion, $floor, $baseCellX, $baseCellY);
        $this->createPackAtCell($mappingVersion, $floor, $baseCellX + 2, $baseCellY);

        // Act
        $count = $this->walkGraphService->calculatePackWalkDistances($mappingVersion, $floor);

        // Assert
        $this->assertSame(0, $count);
        $this->assertFalse(EnemyPackWalkDistance::query()->where('mapping_version_id', $mappingVersion->id)->exists());
    }

    #[Test]
    public function calculatePackWalkDistances_givenGraphWithTooFewRuns_removesTheFloorsDistances(): void
    {
        // Arrange - distances from an earlier build exist, but the graph now has fewer runs than required
        [$floor, $mappingVersion, $baseCellX, $baseCellY] = $this->createFloor();
        $this->createWalkedPaths([[[0, 0], [1, 0], [2, 0]]], $baseCellX, $baseCellY);
        $this->walkGraphService->buildGraph(self::UI_MAP_ID);
        $this->createPackAtCell($mappingVersion, $floor, $baseCellX, $baseCellY);
        $this->createPackAtCell($mappingVersion, $floor, $baseCellX + 2, $baseCellY);
        $this->walkGraphService->calculatePackWalkDistances($mappingVersion, $floor);
        $this->assertTrue(EnemyPackWalkDistance::query()->where('mapping_version_id', $mappingVersion->id)->exists());
        config(['keystoneguru.walk_graph.min_runs' => 2]);

        // Act
        $count = $this->walkGraphService->calculatePackWalkDistances($mappingVersion, $floor);

        // Assert
        $this->assertSame(0, $count);
        $this->assertFalse(EnemyPackWalkDistance::query()->where('mapping_version_id', $mappingVersion->id)->exists());
    }

    #[Test]
    public function buildAll_givenContributionsOnADungeonFloor_buildsTheGraphAndThePackDistances(): void
    {
        // Arrange
        [$floor, $mappingVersion, $baseCellX, $baseCellY] = $this->createFloor();
        $this->createWalkedPaths([[[0, 0], [1, 0], [2, 0]]], $baseCellX, $baseCellY);
        $this->createPackAtCell($mappingVersion, $floor, $baseCellX, $baseCellY);
        $this->createPackAtCell($mappingVersion, $floor, $baseCellX + 2, $baseCellY);

        // Act
        $result = $this->walkGraphService->buildAll();

        // Assert
        $this->assertGreaterThanOrEqual(1, $result['graphs']);
        $this->assertGreaterThanOrEqual(1, $result['distances']);
        $this->assertTrue(WalkGraph::query()->where('ui_map_id', self::UI_MAP_ID)->exists());
        $distance = EnemyPackWalkDistance::query()->where('mapping_version_id', $mappingVersion->id)->sole();
        $this->assertEqualsWithDelta(6.0, $distance->walk_distance, 0.05);
    }

    #[Test]
    public function buildAll_givenSeededGraphWithoutContributions_writesPackDistances(): void
    {
        // Arrange - a graph that came from the seeder, with no combat log contributions of its own
        [$floor, $mappingVersion, $baseCellX, $baseCellY] = $this->createFloor();
        $this->createWalkedPaths([[[0, 0], [1, 0], [2, 0]]], $baseCellX, $baseCellY);
        $this->walkGraphService->buildGraph(self::UI_MAP_ID);
        WalkGraph::query()->where('ui_map_id', self::UI_MAP_ID)->update(['source' => WalkGraphSource::Seeder]);
        CombatLogWalkGraphContribution::query()->where('ui_map_id', self::UI_MAP_ID)->delete();
        $this->createPackAtCell($mappingVersion, $floor, $baseCellX, $baseCellY);
        $this->createPackAtCell($mappingVersion, $floor, $baseCellX + 2, $baseCellY);

        // Act
        $this->walkGraphService->buildAll();

        // Assert
        $distances = EnemyPackWalkDistance::query()->where('mapping_version_id', $mappingVersion->id)->get();
        $this->assertCount(1, $distances);
        $this->assertEqualsWithDelta(6.0, $distances->first()->walk_distance, 0.05);
        $this->assertSame(WalkGraphSource::Seeder, WalkGraph::query()->where('ui_map_id', self::UI_MAP_ID)->sole()->source);
    }

    #[Test]
    public function getFloorSegments_givenGraphOnTheFloor_returnsMergedSegmentsAsMapLocations(): void
    {
        // Arrange - a straight run of three edges walked by 4 runs, then one edge walked by 1 run
        $floor = $this->createBoundedFloor();
        $this->createWalkGraph(5, [
            WalkGrid::edgeKey(10, 10, 11, 10) => 4,
            WalkGrid::edgeKey(11, 10, 12, 10) => 4,
            WalkGrid::edgeKey(12, 10, 13, 10) => 4,
            WalkGrid::edgeKey(13, 10, 13, 11) => 1,
        ]);

        // Act
        $result = $this->walkGraphService->getFloorSegments($floor);

        // Assert
        $this->assertSame(5, $result['run_count']);
        $this->assertSame(1, $result['min_runs']);
        $this->assertSame(4, $result['max_run_count']);
        $this->assertEqualsCanonicalizing([
            $this->expectedSegment($floor, 4, 10, 10, 13, 10),
            $this->expectedSegment($floor, 1, 13, 10, 13, 11),
        ], $result['segments']);
    }

    #[Test]
    public function getFloorSegments_givenEdgesOutsideTheFloorBounds_leavesThemOut(): void
    {
        // Arrange - the floor covers in-game 0..300 yd, so cell 100 (300-303 yd) lies just outside it
        $floor = $this->createBoundedFloor();
        $this->createWalkGraph(1, [
            WalkGrid::edgeKey(10, 10, 11, 10)  => 1,
            WalkGrid::edgeKey(99, 10, 100, 10) => 1,
            WalkGrid::edgeKey(-1, 10, 0, 10)   => 1,
        ]);

        // Act
        $result = $this->walkGraphService->getFloorSegments($floor);

        // Assert
        $this->assertSame([$this->expectedSegment($floor, 1, 10, 10, 11, 10)], $result['segments']);
    }

    #[Test]
    public function getFloorSegments_givenFacadeFloor_returnsNoSegments(): void
    {
        // Arrange
        $floor = $this->createBoundedFloor();
        $floor->update(['facade' => true]);
        $this->createWalkGraph(1, [WalkGrid::edgeKey(10, 10, 11, 10) => 1]);

        // Act
        $result = $this->walkGraphService->getFloorSegments($floor);

        // Assert
        $this->assertSame(0, $result['run_count']);
        $this->assertSame([], $result['segments']);
    }

    #[Test]
    public function getFloorSegments_givenUiMapWithoutGraph_returnsNoSegments(): void
    {
        // Arrange
        $floor = $this->createBoundedFloor();

        // Act
        $result = $this->walkGraphService->getFloorSegments($floor);

        // Assert
        $this->assertSame(0, $result['run_count']);
        $this->assertSame([], $result['segments']);
    }

    #[Test]
    public function getFloorSegments_givenFloorWithoutUiMap_returnsNoSegments(): void
    {
        // Arrange
        $floor = $this->createBoundedFloor();
        $floor->update(['ui_map_id' => null]);
        $this->createWalkGraph(1, [WalkGrid::edgeKey(10, 10, 11, 10) => 1]);

        // Act
        $result = $this->walkGraphService->getFloorSegments($floor);

        // Assert
        $this->assertSame([], $result['segments']);
    }

    /**
     * @param array<int, int> $edgeKeys
     */
    private function createContribution(string $runKey, array $edgeKeys): CombatLogWalkGraphContribution
    {
        return CombatLogWalkGraphContribution::factory()->withEdgeKeys($edgeKeys)->create([
            'ui_map_id'       => self::UI_MAP_ID,
            'run_key'         => $runKey,
            'combat_log_path' => sprintf('/tmp/walk-graph-service-test-%s.txt', $runKey),
        ]);
    }

    /**
     * @param array<int, array<int, array{int, int}>> $paths cell offsets from the base cell
     */
    private function createWalkedPaths(array $paths, int $baseCellX, int $baseCellY): void
    {
        $edgeKeys = [];
        foreach ($paths as $path) {
            for ($i = 1; $i < count($path); $i++) {
                $edgeKeys[] = WalkGrid::edgeKey(
                    $baseCellX + $path[$i - 1][0],
                    $baseCellY + $path[$i - 1][1],
                    $baseCellX + $path[$i][0],
                    $baseCellY + $path[$i][1],
                );
            }
        }

        $this->createContribution('run:1', $edgeKeys);
    }

    /**
     * @return array{Floor, MappingVersion, int, int} the floor, its mapping version and the cell at the floor's centre
     */
    private function createFloor(): array
    {
        $dungeon = $this->createDungeon();
        /** @var Floor $floor */
        $floor = $dungeon->floors()->firstOrFail();
        $floor->update(['ui_map_id' => self::UI_MAP_ID]);

        return [
            $floor,
            $dungeon->mappingVersions()->firstOrFail(),
            WalkGrid::cellCoordinate(($floor->ingame_min_x + $floor->ingame_max_x) / 2, self::CELL_SIZE),
            WalkGrid::cellCoordinate(($floor->ingame_min_y + $floor->ingame_max_y) / 2, self::CELL_SIZE),
        ];
    }

    private function createPackAtCell(MappingVersion $mappingVersion, Floor $floor, int $cellX, int $cellY): EnemyPack
    {
        $enemyPack = EnemyPack::create([
            'mapping_version_id' => $mappingVersion->id,
            'floor_id'           => $floor->id,
            'group'              => 1,
            'label'              => 'Walk graph test pack',
            'teeming'            => null,
            'faction'            => 'any',
            'vertices_json'      => '[]',
        ]);

        $latLng = app(CoordinatesServiceInterface::class)->calculateMapLocationForIngameLocation(
            new IngameXY(($cellX + 0.5) * self::CELL_SIZE, ($cellY + 0.5) * self::CELL_SIZE, $floor),
        );

        Enemy::create([
            'mapping_version_id' => $mappingVersion->id,
            'enemy_pack_id'      => $enemyPack->id,
            'floor_id'           => $floor->id,
            'npc_id'             => 0,
            'teeming'            => null,
            'required'           => true,
            'lat'                => $latLng->getLat(),
            'lng'                => $latLng->getLng(),
        ]);

        return $enemyPack;
    }

    /**
     * A floor on the test ui map covering in-game 0..300 yd on both axes.
     */
    private function createBoundedFloor(): Floor
    {
        [$floor] = $this->createFloor();
        $floor->update([
            'ingame_min_x' => 0,
            'ingame_max_x' => 300,
            'ingame_min_y' => 0,
            'ingame_max_y' => 300,
        ]);

        return $floor;
    }

    /**
     * @param array<int, int> $edgeCounts edge key => run count
     */
    private function createWalkGraph(int $runCount, array $edgeCounts): WalkGraph
    {
        return WalkGraph::factory()->create([
            'ui_map_id' => self::UI_MAP_ID,
            'cell_size' => self::CELL_SIZE,
            'run_count' => $runCount,
            'edges'     => WalkGrid::encodeEdgeCounts($edgeCounts),
        ]);
    }

    /**
     * @return array{int, float, float, float, float}
     */
    private function expectedSegment(Floor $floor, int $runCount, int $fromCellX, int $fromCellY, int $toCellX, int $toCellY): array
    {
        $coordinatesService = app(CoordinatesServiceInterface::class);
        $from               = $coordinatesService->calculateMapLocationForIngameLocation(
            new IngameXY(($fromCellX + 0.5) * self::CELL_SIZE, ($fromCellY + 0.5) * self::CELL_SIZE, $floor),
        );
        $to = $coordinatesService->calculateMapLocationForIngameLocation(
            new IngameXY(($toCellX + 0.5) * self::CELL_SIZE, ($toCellY + 0.5) * self::CELL_SIZE, $floor),
        );

        return [$runCount, round($from->getLat(), 2), round($from->getLng(), 2), round($to->getLat(), 2), round($to->getLng(), 2)];
    }

    private function cleanUpTestData(): void
    {
        $mappingVersionIds = MappingVersion::query()
            ->whereIn('dungeon_id', Dungeon::query()->where('key', 'like', 'test_dungeon_%')->select('id'))
            ->pluck('id');

        // Enemy, EnemyPack and MappingVersion are SeederModels - the query builder removes them
        Enemy::query()->whereIn('mapping_version_id', $mappingVersionIds)->delete();
        EnemyPack::query()->whereIn('mapping_version_id', $mappingVersionIds)->delete();
        EnemyPackWalkDistance::query()->whereIn('mapping_version_id', $mappingVersionIds)->delete();
        CombatLogWalkGraphContribution::query()->where('ui_map_id', self::UI_MAP_ID)->delete();
        WalkGraph::query()->where('ui_map_id', self::UI_MAP_ID)->delete();
    }
}
