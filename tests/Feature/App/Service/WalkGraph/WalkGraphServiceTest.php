<?php

namespace Tests\Feature\App\Service\WalkGraph;

use App\Logic\Structs\IngameXY;
use App\Logic\Structs\LatLng;
use App\Logic\WalkGraph\WalkGrid;
use App\Models\CombatLog\CombatLogWalkGraphContribution;
use App\Models\Dungeon;
use App\Models\DungeonFloorSwitchMarker;
use App\Models\Enemy;
use App\Models\EnemyPack;
use App\Models\Floor\Floor;
use App\Models\Floor\FloorUnion;
use App\Models\Floor\FloorUnionArea;
use App\Models\Mapping\MappingVersion;
use App\Models\WalkGraph\EnemyPackFloorSwitchWalkDistance;
use App\Models\WalkGraph\EnemyPackWalkDistance;
use App\Models\WalkGraph\FloorSwitchMarkerWalkDistance;
use App\Models\WalkGraph\WalkGraph;
use App\Models\WalkGraph\WalkGraphSource;
use App\Service\Cache\CacheService;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\WalkGraph\WalkGraphServiceInterface;
use Database\Factories\FloorFactory;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('WalkGraph')]
final class WalkGraphServiceTest extends PublicTestCase
{
    use CreatesDungeon;

    private const int UI_MAP_ID = 9_990_501;

    private const int OTHER_UI_MAP_ID = 9_990_503;

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
    public function getGridGraphForUiMapId_givenGraphWithEnoughRuns_returnsItsGridGraph(): void
    {
        // Arrange
        config(['keystoneguru.walk_graph.min_runs' => 2]);
        $this->createWalkGraph(2, [WalkGrid::edgeKey(0, 0, 1, 0) => 2, WalkGrid::edgeKey(1, 0, 2, 0) => 1]);

        // Act
        $gridGraph = $this->walkGraphService->getGridGraphForUiMapId(self::UI_MAP_ID);

        // Assert
        $this->assertNotNull($gridGraph);
        $this->assertSame(3, $gridGraph->getNodeCount());
        $this->assertSame(2, $gridGraph->getEdgeCount());
    }

    #[Test]
    public function getGridGraphForUiMapId_givenGraphBelowMinRuns_returnsNull(): void
    {
        // Arrange
        config(['keystoneguru.walk_graph.min_runs' => 2]);
        $this->createWalkGraph(1, [WalkGrid::edgeKey(0, 0, 1, 0) => 1]);

        // Act
        $gridGraph = $this->walkGraphService->getGridGraphForUiMapId(self::UI_MAP_ID);

        // Assert
        $this->assertNull($gridGraph);
    }

    #[Test]
    public function getGridGraphForUiMapId_givenUiMapWithoutGraph_returnsNull(): void
    {
        // Arrange - nothing for this ui map

        // Act
        $gridGraph = $this->walkGraphService->getGridGraphForUiMapId(self::UI_MAP_ID);

        // Assert
        $this->assertNull($gridGraph);
    }

    #[Test]
    public function getGridGraphForUiMapId_calledTwice_returnsTheSameGridGraph(): void
    {
        // Arrange
        $this->createWalkGraph(1, [WalkGrid::edgeKey(0, 0, 1, 0) => 1]);

        // Act
        $first  = $this->walkGraphService->getGridGraphForUiMapId(self::UI_MAP_ID);
        $second = app(WalkGraphServiceInterface::class)->getGridGraphForUiMapId(self::UI_MAP_ID);

        // Assert - also through a second resolution of the service, which the scoped binding makes the same instance
        $this->assertNotNull($first);
        $this->assertSame($first, $second);
    }

    #[Test]
    public function getGridGraphForUiMapId_givenGraphRebuiltSinceTheLastCall_returnsTheRebuiltGridGraph(): void
    {
        // Arrange
        $walkGraph = $this->createWalkGraph(1, [WalkGrid::edgeKey(0, 0, 1, 0) => 1]);
        $before    = $this->walkGraphService->getGridGraphForUiMapId(self::UI_MAP_ID);
        WalkGraph::query()->whereKey($walkGraph->id)->update([
            'edges'    => WalkGrid::encodeEdgeCounts([WalkGrid::edgeKey(0, 0, 1, 0) => 1, WalkGrid::edgeKey(1, 0, 2, 0) => 1]),
            'built_at' => $walkGraph->built_at->addMinute(),
        ]);

        // Act
        $after = $this->walkGraphService->getGridGraphForUiMapId(self::UI_MAP_ID);

        // Assert
        $this->assertSame(2, $before?->getNodeCount());
        $this->assertSame(3, $after?->getNodeCount());
    }

    #[Test]
    public function getGridGraphForUiMapId_givenNewScope_buildsTheGridGraphAgain(): void
    {
        // Arrange
        $this->createWalkGraph(1, [WalkGrid::edgeKey(0, 0, 1, 0) => 1]);
        $first = $this->walkGraphService->getGridGraphForUiMapId(self::UI_MAP_ID);

        // Act - Octane and the queue worker forget scoped instances between requests and jobs
        app()->forgetScopedInstances();
        $second = app(WalkGraphServiceInterface::class)->getGridGraphForUiMapId(self::UI_MAP_ID);

        // Assert
        $this->assertNotNull($first);
        $this->assertNotNull($second);
        $this->assertNotSame($first, $second);
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
    public function calculatePackWalkDistances_givenMarkerBehindWall_writesTheWalkingDistanceAroundIt(): void
    {
        // Arrange - a U-shaped corridor from the pack up, across and down to the floor switch; the wall is between them
        [$floor, $mappingVersion, $baseCellX, $baseCellY] = $this->createFloor();
        $this->createWalkedPaths([
            [[0, 0], [0, 1], [0, 2], [0, 3], [0, 4], [1, 4], [2, 4], [2, 3], [2, 2], [2, 1], [2, 0]],
        ], $baseCellX, $baseCellY);
        $this->walkGraphService->buildGraph(self::UI_MAP_ID);
        $pack   = $this->createPackAtCell($mappingVersion, $floor, $baseCellX, $baseCellY);
        $marker = $this->createMarkerAtCell($mappingVersion, $floor, $baseCellX + 2, $baseCellY);

        // Act
        $this->walkGraphService->calculatePackWalkDistances($mappingVersion, $floor);

        // Assert
        $distance = EnemyPackFloorSwitchWalkDistance::query()->where('mapping_version_id', $mappingVersion->id)->sole();
        $this->assertSame($pack->id, $distance->enemy_pack_id);
        $this->assertSame($marker->id, $distance->dungeon_floor_switch_marker_id);
        $this->assertSame($floor->id, $distance->floor_id);
        $this->assertEqualsWithDelta(6.0, $distance->straight_distance, 0.05);
        $this->assertEqualsWithDelta(30.0, $distance->walk_distance, 0.05);
    }

    #[Test]
    public function calculatePackWalkDistances_givenMarkerFurtherThanTheMaximumPairDistance_stillWritesIt(): void
    {
        // Arrange - packs this far apart get no pair, but a floor switch is useful at any range
        config(['keystoneguru.walk_graph.max_pair_distance' => 5.0]);
        [$floor, $mappingVersion, $baseCellX, $baseCellY] = $this->createFloor();
        $this->createWalkedPaths([[[0, 0], [1, 0], [2, 0]]], $baseCellX, $baseCellY);
        $this->walkGraphService->buildGraph(self::UI_MAP_ID);
        $this->createPackAtCell($mappingVersion, $floor, $baseCellX, $baseCellY);
        $this->createPackAtCell($mappingVersion, $floor, $baseCellX + 2, $baseCellY);
        $this->createMarkerAtCell($mappingVersion, $floor, $baseCellX + 2, $baseCellY);

        // Act
        $count = $this->walkGraphService->calculatePackWalkDistances($mappingVersion, $floor);

        // Assert
        $this->assertSame(0, $count);
        $distances = EnemyPackFloorSwitchWalkDistance::query()->where('mapping_version_id', $mappingVersion->id)->get();
        $this->assertCount(2, $distances);
        $this->assertEqualsWithDelta(6.0, $distances->max('walk_distance'), 0.05);
    }

    #[Test]
    public function calculatePackWalkDistances_givenUnsnappableMarker_writesNoWalkingDistanceForIt(): void
    {
        // Arrange - the second marker is 30 yd from the nearest walked cell, outside the 15 yd snap radius
        [$floor, $mappingVersion, $baseCellX, $baseCellY] = $this->createFloor();
        $this->createWalkedPaths([[[0, 0], [1, 0], [2, 0]]], $baseCellX, $baseCellY);
        $this->walkGraphService->buildGraph(self::UI_MAP_ID);
        $pack              = $this->createPackAtCell($mappingVersion, $floor, $baseCellX, $baseCellY);
        $walkedMarker      = $this->createMarkerAtCell($mappingVersion, $floor, $baseCellX + 2, $baseCellY);
        $unsnappableMarker = $this->createMarkerAtCell($mappingVersion, $floor, $baseCellX + 2, $baseCellY + 10);

        // Act
        $this->walkGraphService->calculatePackWalkDistances($mappingVersion, $floor);

        // Assert
        $packDistances = EnemyPackFloorSwitchWalkDistance::query()
            ->where('mapping_version_id', $mappingVersion->id)
            ->where('enemy_pack_id', $pack->id)
            ->get()
            ->keyBy('dungeon_floor_switch_marker_id');
        $this->assertEqualsWithDelta(6.0, $packDistances[$walkedMarker->id]->walk_distance, 0.05);
        $this->assertEqualsWithDelta(hypot(6, 30), $packDistances[$unsnappableMarker->id]->straight_distance, 0.05);
        $this->assertNull($packDistances[$unsnappableMarker->id]->walk_distance);

        $markerDistance = FloorSwitchMarkerWalkDistance::query()->where('mapping_version_id', $mappingVersion->id)->sole();
        $this->assertEqualsWithDelta(30.0, $markerDistance->straight_distance, 0.05);
        $this->assertNull($markerDistance->walk_distance);
    }

    #[Test]
    public function calculatePackWalkDistances_givenMarkerOnAnotherFloor_writesNothingForIt(): void
    {
        // Arrange - a second floor on the same ui map, with a marker on a walked cell
        [$floor, $mappingVersion, $baseCellX, $baseCellY] = $this->createFloor();
        $otherFloor                                       = FloorFactory::new()->create([
            'dungeon_id'   => $floor->dungeon_id,
            'index'        => 2,
            'name'         => 'Other test floor',
            'default'      => false,
            'ui_map_id'    => self::UI_MAP_ID,
            'ingame_min_x' => $floor->ingame_min_x,
            'ingame_max_x' => $floor->ingame_max_x,
            'ingame_min_y' => $floor->ingame_min_y,
            'ingame_max_y' => $floor->ingame_max_y,
        ]);
        $this->createWalkedPaths([[[0, 0], [1, 0], [2, 0]]], $baseCellX, $baseCellY);
        $this->walkGraphService->buildGraph(self::UI_MAP_ID);
        $this->createPackAtCell($mappingVersion, $floor, $baseCellX, $baseCellY);
        $marker = $this->createMarkerAtCell($mappingVersion, $floor, $baseCellX + 1, $baseCellY);
        $this->createMarkerAtCell($mappingVersion, $otherFloor, $baseCellX + 2, $baseCellY);

        // Act
        $this->walkGraphService->calculatePackWalkDistances($mappingVersion, $floor);

        // Assert
        $markerIds = EnemyPackFloorSwitchWalkDistance::query()
            ->where('mapping_version_id', $mappingVersion->id)
            ->pluck('dungeon_floor_switch_marker_id')
            ->all();
        $this->assertSame([$marker->id], $markerIds);
        $this->assertFalse(FloorSwitchMarkerWalkDistance::query()->where('mapping_version_id', $mappingVersion->id)->exists());
    }

    #[Test]
    public function calculatePackWalkDistances_givenFacadeFloor_writesNoMarkerDistances(): void
    {
        // Arrange - the markers get distances while the floor is not a facade
        [$floor, $mappingVersion, $baseCellX, $baseCellY] = $this->createFloor();
        $this->createWalkedPaths([[[0, 0], [1, 0], [2, 0]]], $baseCellX, $baseCellY);
        $this->walkGraphService->buildGraph(self::UI_MAP_ID);
        $this->createMarkerAtCell($mappingVersion, $floor, $baseCellX + 1, $baseCellY);
        $this->createMarkerAtCell($mappingVersion, $floor, $baseCellX + 2, $baseCellY);
        $this->walkGraphService->calculatePackWalkDistances($mappingVersion, $floor);
        $this->assertTrue(FloorSwitchMarkerWalkDistance::query()->where('mapping_version_id', $mappingVersion->id)->exists());
        $floor->update(['facade' => true]);

        // Act
        $this->walkGraphService->calculatePackWalkDistances($mappingVersion, $floor);

        // Assert
        $this->assertFalse(FloorSwitchMarkerWalkDistance::query()->where('mapping_version_id', $mappingVersion->id)->exists());
    }

    #[Test]
    public function calculatePackWalkDistances_givenTwoMarkersBehindWall_writesTheirWalkingDistanceOnce(): void
    {
        // Arrange - a U-shaped corridor between the two floor switches
        [$floor, $mappingVersion, $baseCellX, $baseCellY] = $this->createFloor();
        $this->createWalkedPaths([
            [[0, 0], [0, 1], [0, 2], [0, 3], [0, 4], [1, 4], [2, 4], [2, 3], [2, 2], [2, 1], [2, 0]],
        ], $baseCellX, $baseCellY);
        $this->walkGraphService->buildGraph(self::UI_MAP_ID);
        $marker      = $this->createMarkerAtCell($mappingVersion, $floor, $baseCellX, $baseCellY);
        $otherMarker = $this->createMarkerAtCell($mappingVersion, $floor, $baseCellX + 2, $baseCellY);

        // Act
        $this->walkGraphService->calculatePackWalkDistances($mappingVersion, $floor);

        // Assert
        $distance = FloorSwitchMarkerWalkDistance::query()->where('mapping_version_id', $mappingVersion->id)->sole();
        $this->assertSame(min($marker->id, $otherMarker->id), $distance->dungeon_floor_switch_marker_id);
        $this->assertSame(max($marker->id, $otherMarker->id), $distance->other_dungeon_floor_switch_marker_id);
        $this->assertSame($floor->id, $distance->floor_id);
        $this->assertEqualsWithDelta(6.0, $distance->straight_distance, 0.05);
        $this->assertEqualsWithDelta(30.0, $distance->walk_distance, 0.05);
    }

    #[Test]
    public function calculatePackWalkDistances_givenGraphWithTooFewRuns_removesTheFloorsMarkerDistances(): void
    {
        // Arrange - marker distances from an earlier build exist, but the graph now has fewer runs than required
        [$floor, $mappingVersion, $baseCellX, $baseCellY] = $this->createFloor();
        $this->createWalkedPaths([[[0, 0], [1, 0], [2, 0]]], $baseCellX, $baseCellY);
        $this->walkGraphService->buildGraph(self::UI_MAP_ID);
        $this->createPackAtCell($mappingVersion, $floor, $baseCellX, $baseCellY);
        $this->createMarkerAtCell($mappingVersion, $floor, $baseCellX + 1, $baseCellY);
        $this->createMarkerAtCell($mappingVersion, $floor, $baseCellX + 2, $baseCellY);
        $this->walkGraphService->calculatePackWalkDistances($mappingVersion, $floor);
        $this->assertTrue(EnemyPackFloorSwitchWalkDistance::query()->where('mapping_version_id', $mappingVersion->id)->exists());
        $this->assertTrue(FloorSwitchMarkerWalkDistance::query()->where('mapping_version_id', $mappingVersion->id)->exists());
        config(['keystoneguru.walk_graph.min_runs' => 2]);

        // Act
        $this->walkGraphService->calculatePackWalkDistances($mappingVersion, $floor);

        // Assert
        $this->assertFalse(EnemyPackFloorSwitchWalkDistance::query()->where('mapping_version_id', $mappingVersion->id)->exists());
        $this->assertFalse(FloorSwitchMarkerWalkDistance::query()->where('mapping_version_id', $mappingVersion->id)->exists());
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
    public function getWalkPaths_givenWallBetweenPoints_returnsTheWalkAroundItBothWays(): void
    {
        // Arrange - a U-shaped corridor from the first point up, across and down to the second; the wall is between them
        [$floor, , $baseCellX, $baseCellY] = $this->createFloor();
        $this->createWalkedPaths([
            [[0, 0], [0, 1], [0, 2], [0, 3], [0, 4], [1, 4], [2, 4], [2, 3], [2, 2], [2, 1], [2, 0]],
        ], $baseCellX, $baseCellY);
        $this->walkGraphService->buildGraph(self::UI_MAP_ID);
        $from = $this->cellCentre($floor, $baseCellX, $baseCellY);
        $to   = $this->cellCentre($floor, $baseCellX + 2, $baseCellY);

        // Act
        $walkPaths = $this->walkGraphService->getWalkPaths($floor, ['from' => $from, 'to' => $to]);

        // Assert - the corners of the corridor shape the path, every step along a side is simplified away
        $expectedPath = [
            [$from->getX(), $from->getY()],
            [$from->getX(), $from->getY() + 4 * self::CELL_SIZE],
            [$to->getX(), $to->getY() + 4 * self::CELL_SIZE],
            [$to->getX(), $to->getY()],
        ];
        $this->assertEqualsWithDelta(30.0, $walkPaths['from']['to']['distance'], 0.01);
        $this->assertEqualsWithDelta(30.0, $walkPaths['to']['from']['distance'], 0.01);
        $this->assertSame($expectedPath, $this->toPositions($walkPaths['from']['to']['path']));
        $this->assertSame(array_reverse($expectedPath), $this->toPositions($walkPaths['to']['from']['path']));
        $this->assertSame($floor, $walkPaths['from']['to']['path'][1]->getFloor());
    }

    #[Test]
    public function getWalkPaths_givenPointNobodyWalkedNear_leavesItsPairsOut(): void
    {
        // Arrange - the third point is 54 yards from the nearest walked cell, beyond the snap radius
        [$floor, , $baseCellX, $baseCellY] = $this->createFloor();
        $this->createWalkedPaths([[[0, 0], [1, 0], [2, 0]]], $baseCellX, $baseCellY);
        $this->walkGraphService->buildGraph(self::UI_MAP_ID);

        // Act
        $walkPaths = $this->walkGraphService->getWalkPaths($floor, [
            'a'   => $this->cellCentre($floor, $baseCellX, $baseCellY),
            'b'   => $this->cellCentre($floor, $baseCellX + 2, $baseCellY),
            'far' => $this->cellCentre($floor, $baseCellX + 20, $baseCellY),
        ]);

        // Assert
        $this->assertSame(['a', 'b'], array_keys($walkPaths));
        $this->assertSame(['b'], array_keys($walkPaths['a']));
        $this->assertSame(['a'], array_keys($walkPaths['b']));
    }

    #[Test]
    public function getWalkPaths_givenGraphWithTooFewRuns_returnsNothing(): void
    {
        // Arrange
        config(['keystoneguru.walk_graph.min_runs' => 2]);
        [$floor, , $baseCellX, $baseCellY] = $this->createFloor();
        $this->createWalkGraph(1, [WalkGrid::edgeKey($baseCellX, $baseCellY, $baseCellX + 1, $baseCellY) => 1]);

        // Act
        $walkPaths = $this->walkGraphService->getWalkPaths($floor, [
            'a' => $this->cellCentre($floor, $baseCellX, $baseCellY),
            'b' => $this->cellCentre($floor, $baseCellX + 1, $baseCellY),
        ]);

        // Assert
        $this->assertSame([], $walkPaths);
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

    #[Test]
    public function getFloorArea_givenGraphOnTheFloor_returnsTheOutlineAsMapLocations(): void
    {
        // Arrange - one edge, outlined without growing, simplifying or smoothing
        $this->setUnchangedAreaOutline();
        $floor = $this->createBoundedFloor();
        $this->createWalkGraph(5, [WalkGrid::edgeKey(10, 10, 11, 10) => 2]);

        // Act
        $result = $this->walkGraphService->getFloorArea($floor);

        // Assert
        $this->assertSame(5, $result['run_count']);
        $this->assertSame(1, $result['min_runs']);
        $this->assertCount(1, $result['rings']);
        $this->assertEqualsCanonicalizing([
            $this->expectedAreaCorner($floor, 10, 10),
            $this->expectedAreaCorner($floor, 12, 10),
            $this->expectedAreaCorner($floor, 12, 11),
            $this->expectedAreaCorner($floor, 10, 11),
        ], $result['rings'][0]);
    }

    #[Test]
    public function getFloorArea_givenEdgesOutsideTheFloorBounds_leavesThemOut(): void
    {
        // Arrange - the floor covers in-game 0..300 yd, so cell 100 (300-303 yd) lies just outside it
        $this->setUnchangedAreaOutline();
        $floor = $this->createBoundedFloor();
        $this->createWalkGraph(1, [
            WalkGrid::edgeKey(10, 10, 11, 10)  => 1,
            WalkGrid::edgeKey(99, 10, 100, 10) => 1,
        ]);

        // Act
        $result = $this->walkGraphService->getFloorArea($floor);

        // Assert
        $this->assertCount(1, $result['rings']);
        $this->assertContains($this->expectedAreaCorner($floor, 10, 10), $result['rings'][0]);
    }

    #[Test]
    public function getFloorArea_givenFacadeFloor_returnsNoRings(): void
    {
        // Arrange
        $floor = $this->createBoundedFloor();
        $floor->update(['facade' => true]);
        $this->createWalkGraph(1, [WalkGrid::edgeKey(10, 10, 11, 10) => 1]);

        // Act
        $result = $this->walkGraphService->getFloorArea($floor);

        // Assert
        $this->assertSame(0, $result['run_count']);
        $this->assertSame([], $result['rings']);
    }

    #[Test]
    public function getFloorArea_givenUiMapWithoutGraph_returnsNoRings(): void
    {
        // Arrange
        $floor = $this->createBoundedFloor();

        // Act
        $result = $this->walkGraphService->getFloorArea($floor);

        // Assert
        $this->assertSame(0, $result['run_count']);
        $this->assertSame([], $result['rings']);
    }

    #[Test]
    public function getFloorArea_givenAreaWithAHole_windsTheHoleAgainstItsOutline(): void
    {
        // Arrange - a walked square loop of 5x5 cells around an unwalked 3x3 hole
        $this->setUnchangedAreaOutline();
        $floor      = $this->createBoundedFloor();
        $edgeCounts = [];
        for ($i = 10; $i < 14; $i++) {
            $edgeCounts[WalkGrid::edgeKey($i, 10, $i + 1, 10)] = 1;
            $edgeCounts[WalkGrid::edgeKey($i, 14, $i + 1, 14)] = 1;
            $edgeCounts[WalkGrid::edgeKey(10, $i, 10, $i + 1)] = 1;
            $edgeCounts[WalkGrid::edgeKey(14, $i, 14, $i + 1)] = 1;
        }
        $this->createWalkGraph(1, $edgeCounts);

        // Act
        $result = $this->walkGraphService->getFloorArea($floor);

        // Assert
        $this->assertCount(2, $result['rings']);
        $this->assertLessThan(0, $this->signedRingArea($result['rings'][0]) * $this->signedRingArea($result['rings'][1]));
    }

    #[Test]
    public function getFacadeFloorArea_givenFacadeOverAGraphedFloor_returnsItsRingsInFacadeCoordinates(): void
    {
        // Arrange
        $this->setUnchangedAreaOutline();
        [$mappingVersion, $facadeFloor, $floor] = $this->createFacade();
        $floorUnion                             = $this->createFloorUnion($mappingVersion, $facadeFloor, $floor, -100, 150, 80, 30);
        $this->createWalkGraph(5, [WalkGrid::edgeKey(10, 10, 11, 10) => 2]);
        $floorRing = $this->walkGraphService->getFloorArea($floor)['rings'][0];

        // Act
        $result = $this->walkGraphService->getFacadeFloorArea($mappingVersion, $facadeFloor);

        // Assert
        $this->assertSame(5, $result['run_count']);
        $this->assertCount(1, $result['rings']);
        $this->assertEqualsCanonicalizing($this->expectedFacadeRing($mappingVersion, $floor, $floorRing, $floorUnion), $result['rings'][0]);
        $this->assertNotEqualsCanonicalizing($floorRing, $result['rings'][0]);
    }

    #[Test]
    public function getFacadeFloorArea_givenFacadeOverFloorsWithoutAGraph_returnsNoRings(): void
    {
        // Arrange
        [$mappingVersion, $facadeFloor, $floor] = $this->createFacade();
        $this->createFloorUnion($mappingVersion, $facadeFloor, $floor, -100, 150, 80, 0);

        // Act
        $result = $this->walkGraphService->getFacadeFloorArea($mappingVersion, $facadeFloor);

        // Assert
        $this->assertSame(0, $result['run_count']);
        $this->assertSame([], $result['rings']);
    }

    #[Test]
    public function getFacadeFloorArea_givenOneOfTwoFloorsWithAGraph_returnsOnlyThatFloorsRings(): void
    {
        // Arrange
        $this->setUnchangedAreaOutline();
        [$mappingVersion, $facadeFloor, $floor] = $this->createFacade();
        $ungraphedFloor                         = $this->createFacadeChildFloor($floor, self::OTHER_UI_MAP_ID, 2);
        $this->createFloorUnion($mappingVersion, $facadeFloor, $floor, -100, 100, 80, 0);
        $this->createFloorUnion($mappingVersion, $facadeFloor, $ungraphedFloor, -100, 250, 80, 0);
        $this->createWalkGraph(5, [WalkGrid::edgeKey(10, 10, 11, 10) => 2]);

        // Act
        $result = $this->walkGraphService->getFacadeFloorArea($mappingVersion, $facadeFloor);

        // Assert
        $this->assertSame(5, $result['run_count']);
        $this->assertCount(1, $result['rings']);
    }

    #[Test]
    public function getFacadeFloorArea_givenFloorsWithMirroredAxes_windsEveryFloorsOutlineTheSameWay(): void
    {
        // Arrange - the second floor's in-game x axis runs the other way, which mirrors its rings
        $this->setUnchangedAreaOutline();
        [$mappingVersion, $facadeFloor, $floor] = $this->createFacade();
        $mirroredFloor                          = $this->createFacadeChildFloor($floor, self::OTHER_UI_MAP_ID, 2);
        $mirroredFloor->update(['ingame_min_x' => 300, 'ingame_max_x' => 0]);
        $this->createFloorUnion($mappingVersion, $facadeFloor, $floor, -100, 150, 80, 0);
        $this->createFloorUnion($mappingVersion, $facadeFloor, $mirroredFloor, -110, 160, 80, 0);
        $this->createWalkGraph(5, [WalkGrid::edgeKey(10, 10, 11, 10) => 2]);
        $this->createWalkGraph(5, [WalkGrid::edgeKey(10, 10, 11, 10) => 2], self::OTHER_UI_MAP_ID);
        $floorRingArea         = $this->signedRingArea($this->walkGraphService->getFloorArea($floor)['rings'][0]);
        $mirroredFloorRingArea = $this->signedRingArea($this->walkGraphService->getFloorArea($mirroredFloor)['rings'][0]);

        // Act
        $result = $this->walkGraphService->getFacadeFloorArea($mappingVersion, $facadeFloor);

        // Assert
        $this->assertLessThan(0, $floorRingArea * $mirroredFloorRingArea, 'The arrangement should mirror the second floor');
        $this->assertCount(2, $result['rings']);
        $this->assertGreaterThan(0, $this->signedRingArea($result['rings'][0]) * $this->signedRingArea($result['rings'][1]));
    }

    #[Test]
    public function getFacadeFloorArea_givenFloorOnTwoFloorUnions_movesTheRingThroughTheUnionWhoseAreaHoldsIt(): void
    {
        // Arrange - only the second floor union has an area, and it covers that union's whole footprint
        $this->setUnchangedAreaOutline();
        [$mappingVersion, $facadeFloor, $floor] = $this->createFacade();
        $this->createFloorUnion($mappingVersion, $facadeFloor, $floor, -60, 100, 80, 0);
        $secondFloorUnion = $this->createFloorUnion($mappingVersion, $facadeFloor, $floor, -180, 280, 80, 0);
        FloorUnionArea::create([
            'mapping_version_id' => $mappingVersion->id,
            'floor_id'           => $facadeFloor->id,
            'floor_union_id'     => $secondFloorUnion->id,
            'vertices_json'      => json_encode([
                ['lat' => -250, 'lng' => 200],
                ['lat' => -250, 'lng' => 360],
                ['lat' => -110, 'lng' => 360],
                ['lat' => -110, 'lng' => 200],
            ]),
        ]);
        $this->createWalkGraph(5, [WalkGrid::edgeKey(10, 10, 11, 10) => 2]);
        $floorRing = $this->walkGraphService->getFloorArea($floor)['rings'][0];

        // Act
        $result = $this->walkGraphService->getFacadeFloorArea($mappingVersion->fresh(), $facadeFloor);

        // Assert
        $this->assertCount(1, $result['rings']);
        $this->assertEqualsCanonicalizing($this->expectedFacadeRing($mappingVersion, $floor, $floorRing, $secondFloorUnion), $result['rings'][0]);
    }

    #[Test]
    public function getFacadeFloorArea_givenMappingVersionWithoutFacade_returnsNoRings(): void
    {
        // Arrange
        $this->setUnchangedAreaOutline();
        [$mappingVersion, $facadeFloor, $floor] = $this->createFacade();
        $this->createFloorUnion($mappingVersion, $facadeFloor, $floor, -100, 150, 80, 0);
        $this->createWalkGraph(5, [WalkGrid::edgeKey(10, 10, 11, 10) => 2]);
        $mappingVersion->update(['facade_enabled' => false]);

        // Act
        $result = $this->walkGraphService->getFacadeFloorArea($mappingVersion, $facadeFloor);

        // Assert
        $this->assertSame([], $result['rings']);
    }

    #[Test]
    public function getFacadeFloorArea_givenTheSameGraphBuild_returnsTheCachedRings(): void
    {
        // Arrange - the floor union moves without a new graph build, which only a cached facade area hides
        $this->useRealCache();
        $this->setUnchangedAreaOutline();
        [$mappingVersion, $facadeFloor, $floor] = $this->createFacade();
        $floorUnion                             = $this->createFloorUnion($mappingVersion, $facadeFloor, $floor, -100, 150, 80, 0);
        $this->createWalkGraph(5, [WalkGrid::edgeKey(10, 10, 11, 10) => 2]);
        $firstRings = $this->walkGraphService->getFacadeFloorArea($mappingVersion, $facadeFloor)['rings'];
        $floorUnion->update(['lat' => -150, 'lng' => 250]);

        // Act
        $result = $this->walkGraphService->getFacadeFloorArea($mappingVersion->fresh(), $facadeFloor);

        // Assert
        $this->assertSame($firstRings, $result['rings']);
    }

    #[Test]
    public function getFacadeFloorArea_givenTheGraphWasRebuilt_returnsTheNewBuildsRings(): void
    {
        // Arrange
        $this->useRealCache();
        $this->setUnchangedAreaOutline();
        [$mappingVersion, $facadeFloor, $floor] = $this->createFacade();
        $this->createFloorUnion($mappingVersion, $facadeFloor, $floor, -100, 150, 80, 0);
        $walkGraph  = $this->createWalkGraph(5, [WalkGrid::edgeKey(10, 10, 11, 10) => 2]);
        $firstRings = $this->walkGraphService->getFacadeFloorArea($mappingVersion, $facadeFloor)['rings'];
        $walkGraph->update([
            'edges'    => WalkGrid::encodeEdgeCounts([WalkGrid::edgeKey(50, 50, 51, 50) => 2]),
            'built_at' => Carbon::now()->addHour(),
        ]);

        // Act
        $result = $this->walkGraphService->getFacadeFloorArea($mappingVersion, $facadeFloor);

        // Assert
        $this->assertCount(1, $result['rings']);
        $this->assertNotEquals($firstRings, $result['rings']);
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

    private function createMarkerAtCell(MappingVersion $mappingVersion, Floor $floor, int $cellX, int $cellY): DungeonFloorSwitchMarker
    {
        $latLng = app(CoordinatesServiceInterface::class)->calculateMapLocationForIngameLocation(
            new IngameXY(($cellX + 0.5) * self::CELL_SIZE, ($cellY + 0.5) * self::CELL_SIZE, $floor),
        );

        return DungeonFloorSwitchMarker::create([
            'mapping_version_id'                    => $mappingVersion->id,
            'floor_id'                              => $floor->id,
            'target_floor_id'                       => $floor->id,
            'source_floor_id'                       => null,
            'linked_dungeon_floor_switch_marker_id' => null,
            'direction'                             => null,
            'hidden_in_facade'                      => false,
            'lat'                                   => $latLng->getLat(),
            'lng'                                   => $latLng->getLng(),
        ]);
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
     * The test environment binds a cache service that never stores anything; this one stores in the array cache store.
     */
    private function useRealCache(): void
    {
        $this->app->instance(CacheServiceInterface::class, $this->app->make(CacheService::class));
        $this->app->forgetScopedInstances();
        $this->walkGraphService = $this->app->make(WalkGraphServiceInterface::class);
    }

    /**
     * A facade-enabled mapping version with a facade floor and a bounded floor on the test ui map below it.
     *
     * @return array{MappingVersion, Floor, Floor} the mapping version, the facade floor and the floor below it
     */
    private function createFacade(): array
    {
        $dungeon = $this->createDungeon(mappingVersionAttributes: ['facade_enabled' => true]);
        /** @var Floor $floor */
        $floor = $dungeon->floors()->firstOrFail();
        $floor->update([
            'ui_map_id'    => self::UI_MAP_ID,
            'ingame_min_x' => 0,
            'ingame_max_x' => 300,
            'ingame_min_y' => 0,
            'ingame_max_y' => 300,
        ]);

        $facadeFloor = FloorFactory::new()->create([
            'dungeon_id'   => $dungeon->id,
            'index'        => 99,
            'name'         => 'Test Facade Floor',
            'facade'       => true,
            'ui_map_id'    => null,
            'ingame_min_x' => 0,
            'ingame_max_x' => 0,
            'ingame_min_y' => 0,
            'ingame_max_y' => 0,
        ]);

        return [$dungeon->mappingVersions()->firstOrFail(), $facadeFloor, $floor];
    }

    private function createFacadeChildFloor(Floor $floor, int $uiMapId, int $index): Floor
    {
        return FloorFactory::new()->create([
            'dungeon_id'   => $floor->dungeon_id,
            'index'        => $index,
            'name'         => 'Test Floor',
            'ui_map_id'    => $uiMapId,
            'ingame_min_x' => 0,
            'ingame_max_x' => 300,
            'ingame_min_y' => 0,
            'ingame_max_y' => 300,
        ]);
    }

    private function createFloorUnion(
        MappingVersion $mappingVersion,
        Floor          $facadeFloor,
        Floor          $floor,
        float          $lat,
        float          $lng,
        float          $size,
        float          $rotation,
    ): FloorUnion {
        return FloorUnion::create([
            'mapping_version_id' => $mappingVersion->id,
            'floor_id'           => $facadeFloor->id,
            'target_floor_id'    => $floor->id,
            'lat'                => $lat,
            'lng'                => $lng,
            'size'               => $size,
            'rotation'           => $rotation,
        ]);
    }

    /**
     * @param list<array{float, float}> $floorRing [lat, lng] on the floor
     *
     * @return list<array{float, float}> [lat, lng] on the facade
     */
    private function expectedFacadeRing(MappingVersion $mappingVersion, Floor $floor, array $floorRing, FloorUnion $floorUnion): array
    {
        $coordinatesService = app(CoordinatesServiceInterface::class);

        return array_map(static function (array $corner) use ($coordinatesService, $mappingVersion, $floor, $floorUnion): array {
            $latLng = $coordinatesService->convertMapLocationToFacadeMapLocation(
                $mappingVersion,
                new LatLng($corner[0], $corner[1], $floor),
                $floorUnion->fresh(),
            );

            return [round($latLng->getLat(), 2), round($latLng->getLng(), 2)];
        }, $floorRing);
    }

    /**
     * @param list<array{float, float}> $ring [lat, lng]
     */
    private function signedRingArea(array $ring): float
    {
        $area = 0.0;
        for ($i = 0; $i < count($ring); $i++) {
            [$lat, $lng]         = $ring[$i];
            [$nextLat, $nextLng] = $ring[($i + 1) % count($ring)];
            $area += $lng * $nextLat - $nextLng * $lat;
        }

        return $area / 2;
    }

    private function cellCentre(Floor $floor, int $cellX, int $cellY): IngameXY
    {
        return new IngameXY(($cellX + 0.5) * self::CELL_SIZE, ($cellY + 0.5) * self::CELL_SIZE, $floor);
    }

    /**
     * @param list<IngameXY> $path
     *
     * @return list<array{float, float}>
     */
    private function toPositions(array $path): array
    {
        return array_map(static fn(IngameXY $ingameXY): array => [$ingameXY->getX(), $ingameXY->getY()], $path);
    }

    /**
     * @param array<int, int> $edgeCounts edge key => run count
     */
    private function createWalkGraph(int $runCount, array $edgeCounts, int $uiMapId = self::UI_MAP_ID): WalkGraph
    {
        return WalkGraph::factory()->create([
            'ui_map_id' => $uiMapId,
            'cell_size' => self::CELL_SIZE,
            'run_count' => $runCount,
            'edges'     => WalkGrid::encodeEdgeCounts($edgeCounts),
        ]);
    }

    /**
     * Outlines exactly the walked cells, so a ring's corners are the cell corners.
     */
    private function setUnchangedAreaOutline(): void
    {
        config([
            'keystoneguru.walk_graph.area.grow_cells'         => 0,
            'keystoneguru.walk_graph.area.close_cells'        => 0,
            'keystoneguru.walk_graph.area.min_region_cells'   => 1,
            'keystoneguru.walk_graph.area.simplify_tolerance' => 0.0,
            'keystoneguru.walk_graph.area.smooth_iterations'  => 0,
        ]);
    }

    /**
     * @return array{float, float} [lat, lng] of the cell corner
     */
    private function expectedAreaCorner(Floor $floor, int $cornerX, int $cornerY): array
    {
        $latLng = app(CoordinatesServiceInterface::class)->calculateMapLocationForIngameLocation(
            new IngameXY($cornerX * self::CELL_SIZE, $cornerY * self::CELL_SIZE, $floor),
        );

        return [round($latLng->getLat(), 2), round($latLng->getLng(), 2)];
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

        // Enemy, EnemyPack, DungeonFloorSwitchMarker and MappingVersion are SeederModels - the query builder removes them
        DungeonFloorSwitchMarker::query()->whereIn('mapping_version_id', $mappingVersionIds)->delete();
        Enemy::query()->whereIn('mapping_version_id', $mappingVersionIds)->delete();
        EnemyPack::query()->whereIn('mapping_version_id', $mappingVersionIds)->delete();
        EnemyPackWalkDistance::query()->whereIn('mapping_version_id', $mappingVersionIds)->delete();
        EnemyPackFloorSwitchWalkDistance::query()->whereIn('mapping_version_id', $mappingVersionIds)->delete();
        FloorSwitchMarkerWalkDistance::query()->whereIn('mapping_version_id', $mappingVersionIds)->delete();
        CombatLogWalkGraphContribution::query()->where('ui_map_id', self::UI_MAP_ID)->delete();
        WalkGraph::query()->whereIn('ui_map_id', [self::UI_MAP_ID, self::OTHER_UI_MAP_ID])->delete();
    }
}
