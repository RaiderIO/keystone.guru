<?php

namespace Tests\Feature\App\Service\KillZonePath;

use App\Features\WalkGraphKillZonePaths;
use App\Logic\Structs\IngameXY;
use App\Logic\Structs\LatLng;
use App\Logic\WalkGraph\WalkGrid;
use App\Models\DungeonFloorSwitchMarker;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Models\KillZone\KillZone;
use App\Models\Mapping\MappingVersion;
use App\Models\WalkGraph\WalkGraph;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\KillZonePath\KillZonePathServiceInterface;
use Database\Factories\FloorFactory;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('KillZonePath')]
final class KillZonePathServiceWalkGraphTest extends PublicTestCase
{
    use CreatesDungeon;

    private const int UI_MAP_ID = 9_990_601;

    private const float CELL_SIZE = 3.0;

    /** A U-shaped corridor up, across and down: the wall stands between cell [0, 0] and cell [2, 0] */
    private const array CORRIDOR = [[0, 0], [0, 1], [0, 2], [0, 3], [0, 4], [1, 4], [2, 4], [2, 3], [2, 2], [2, 1], [2, 0]];

    private Floor $floor;

    private MappingVersion $mappingVersion;

    private int $baseCellX;

    private int $baseCellY;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'broadcasting.default'                      => 'null',
            'keystoneguru.walk_graph.min_runs'          => 1,
            'keystoneguru.walk_graph.floor_switch_cost' => 0.0,
            // The corridors below are tiny, so their detours are many times the straight line
            'keystoneguru.walk_graph.max_detour_ratio'      => 20.0,
            'keystoneguru.walk_graph.straight_line_penalty' => 1.2,
        ]);
        WalkGraph::query()->where('ui_map_id', self::UI_MAP_ID)->delete();

        $dungeon = $this->createDungeon();
        /** @var Floor $floor */
        $floor = $dungeon->floors()->firstOrFail();
        $floor->update(['ui_map_id' => self::UI_MAP_ID]);

        $this->floor          = $floor;
        $this->mappingVersion = $dungeon->mappingVersions()->firstOrFail();
        $this->baseCellX      = WalkGrid::cellCoordinate(($floor->ingame_min_x + $floor->ingame_max_x) / 2, self::CELL_SIZE);
        $this->baseCellY      = WalkGrid::cellCoordinate(($floor->ingame_min_y + $floor->ingame_max_y) / 2, self::CELL_SIZE);
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            DungeonFloorSwitchMarker::query()->where('mapping_version_id', $this->mappingVersion->id)->delete();
            WalkGraph::query()->where('ui_map_id', self::UI_MAP_ID)->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function findPathsToKillZones_givenWallBetweenPulls_returnsPathAroundTheWall(): void
    {
        // Arrange
        $this->createCorridorWalkGraph(1);
        $dungeonRoute = $this->createDungeonRoute();

        try {
            $this->createKillZoneAtCell($dungeonRoute, $this->floor, 0, 0, 1);
            $killZone = $this->createKillZoneAtCell($dungeonRoute, $this->floor, 2, 0, 2);

            // Act
            $result = app(KillZonePathServiceInterface::class)->findPathsToKillZones($dungeonRoute);

            // Assert - up the corridor, across the top and down again: 12 + 6 + 12 yards instead of 6
            $this->assertSame([
                $this->cellLocation($this->floor, 0, 0),
                $this->cellLocation($this->floor, 0, 4),
                $this->cellLocation($this->floor, 2, 4),
                $this->cellLocation($this->floor, 2, 0),
            ], $this->toLocations($result[$killZone->id]));
            $this->assertEqualsWithDelta(30.0, $this->ingameLength($result[$killZone->id]), 0.01);
        } finally {
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function findPathsToKillZones_givenUnsnappablePullAcrossTheWall_doesNotShortcutThroughIt(): void
    {
        // Arrange - a corridor 10 cells deep; the third pull stands 18 yd off it, two straight 18 yd lines from both
        $this->createCorridorWalkGraph(1, [[0, 0], [0, 10], [2, 10], [2, 0]]);
        $dungeonRoute = $this->createDungeonRoute();

        try {
            $this->createKillZoneAtCell($dungeonRoute, $this->floor, 0, 0, 1);
            $killZone = $this->createKillZoneAtCell($dungeonRoute, $this->floor, 2, 0, 2);
            $this->createKillZoneAtCell($dungeonRoute, $this->floor, 1, -6, 3);

            // Act
            $result = app(KillZonePathServiceInterface::class)->findPathsToKillZones($dungeonRoute);

            // Assert
            $this->assertSame([
                $this->cellLocation($this->floor, 0, 0),
                $this->cellLocation($this->floor, 0, 10),
                $this->cellLocation($this->floor, 2, 10),
                $this->cellLocation($this->floor, 2, 0),
            ], $this->toLocations($result[$killZone->id]));
        } finally {
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function findPathsToKillZones_givenWalkLongerThanTheMaxDetourRatio_returnsStraightSegment(): void
    {
        // Arrange - the walk around the wall is 30 yd against 6 yd straight: a ratio of 5
        config(['keystoneguru.walk_graph.max_detour_ratio' => 4.0]);
        $this->createCorridorWalkGraph(1);
        $dungeonRoute = $this->createDungeonRoute();

        try {
            $this->createKillZoneAtCell($dungeonRoute, $this->floor, 0, 0, 1);
            $killZone = $this->createKillZoneAtCell($dungeonRoute, $this->floor, 2, 0, 2);

            // Act
            $result = app(KillZonePathServiceInterface::class)->findPathsToKillZones($dungeonRoute);

            // Assert
            $this->assertSame([
                $this->cellLocation($this->floor, 0, 0),
                $this->cellLocation($this->floor, 2, 0),
            ], $this->toLocations($result[$killZone->id]));
        } finally {
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function findPathsToKillZones_givenUnwalkedLegOnAWalkedFloor_penalisesItsStraightLine(): void
    {
        // Arrange - the next pull is 60 yd straight and off the graph, which costs 72 yd with the penalty. A detour
        // over the other floor costs 1.8 + 57 + 3 x 1.2 = 62.4 yd: longer than the straight line, shorter than its
        // penalty. The first switch is off centre, so it snaps onto the pull's cell rather than the one past the wall
        $this->createCorridorWalkGraph(1);
        $otherFloor   = $this->createOtherFloor();
        $dungeonRoute = $this->createDungeonRoute();

        try {
            $this->createLinkedMarkers($this->floor, 0.6, 0, $otherFloor, 0, 0);
            $this->createLinkedMarkers($this->floor, 19, 0, $otherFloor, 19, 0);
            $this->createKillZoneAtCell($dungeonRoute, $this->floor, 0, 0, 1);
            $killZone = $this->createKillZoneAtCell($dungeonRoute, $this->floor, 20, 0, 2);

            // Act
            $result = app(KillZonePathServiceInterface::class)->findPathsToKillZones($dungeonRoute);

            // Assert
            $this->assertSame([
                $this->cellLocation($this->floor, 0, 0),
                $this->cellLocation($this->floor, 0.6, 0),
                $this->cellLocation($otherFloor, 0, 0),
                $this->cellLocation($otherFloor, 19, 0),
                $this->cellLocation($this->floor, 19, 0),
                $this->cellLocation($this->floor, 20, 0),
            ], $this->toLocations($result[$killZone->id]));
        } finally {
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function findPathsToKillZones_givenUnsnappableKillZone_returnsStraightSegment(): void
    {
        // Arrange - 54 yards from the nearest walked cell, beyond the snap radius
        $this->createCorridorWalkGraph(1);
        $dungeonRoute = $this->createDungeonRoute();

        try {
            $this->createKillZoneAtCell($dungeonRoute, $this->floor, 0, 0, 1);
            $killZone = $this->createKillZoneAtCell($dungeonRoute, $this->floor, 20, 0, 2);

            // Act
            $result = app(KillZonePathServiceInterface::class)->findPathsToKillZones($dungeonRoute);

            // Assert
            $this->assertSame([
                $this->cellLocation($this->floor, 0, 0),
                $this->cellLocation($this->floor, 20, 0),
            ], $this->toLocations($result[$killZone->id]));
        } finally {
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function findPathsToKillZones_givenGraphBelowMinRuns_returnsStraightSegment(): void
    {
        // Arrange
        config(['keystoneguru.walk_graph.min_runs' => 5]);
        $this->createCorridorWalkGraph(4);
        $dungeonRoute = $this->createDungeonRoute();

        try {
            $this->createKillZoneAtCell($dungeonRoute, $this->floor, 0, 0, 1);
            $killZone = $this->createKillZoneAtCell($dungeonRoute, $this->floor, 2, 0, 2);

            // Act
            $result = app(KillZonePathServiceInterface::class)->findPathsToKillZones($dungeonRoute);

            // Assert
            $this->assertSame([
                $this->cellLocation($this->floor, 0, 0),
                $this->cellLocation($this->floor, 2, 0),
            ], $this->toLocations($result[$killZone->id]));
        } finally {
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function findPathsToKillZones_givenTwoWayLinkedMarkers_keepsCrossFloorEdge(): void
    {
        // Arrange - the floor switch stands behind the wall, the next pull is on the other floor
        $this->createCorridorWalkGraph(1);
        $otherFloor   = $this->createOtherFloor();
        $dungeonRoute = $this->createDungeonRoute();

        try {
            [$marker, $otherMarker] = $this->createLinkedMarkers($this->floor, 2, 0, $otherFloor, 0, 0);
            $this->createKillZoneAtCell($dungeonRoute, $this->floor, 0, 0, 1);
            $killZone = $this->createKillZoneAtCell($dungeonRoute, $otherFloor, 5, 0, 2);

            // Act
            $result = app(KillZonePathServiceInterface::class)->findPathsToKillZones($dungeonRoute);

            // Assert
            $this->assertSame([
                $this->cellLocation($this->floor, 0, 0),
                $this->cellLocation($this->floor, 0, 4),
                $this->cellLocation($this->floor, 2, 4),
                $this->cellLocation($this->floor, 2, 0),
                $this->cellLocation($otherFloor, 0, 0),
                $this->cellLocation($otherFloor, 5, 0),
            ], $this->toLocations($result[$killZone->id]));
            $this->assertSame($marker->floor_id, $result[$killZone->id][3]->getFloor()->id);
            $this->assertSame($otherMarker->floor_id, $result[$killZone->id][4]->getFloor()->id);
        } finally {
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function findPathsToKillZones_givenFloorSwitchCostAboveTheDetour_returnsTheSameFloorPath(): void
    {
        // Arrange - a shortcut over the other floor: two switches 3 yards from each pull, 3 yards apart over there
        $otherFloor   = $this->createOtherFloor();
        $dungeonRoute = $this->createDungeonRoute();

        try {
            $this->createLinkedMarkers($this->floor, 1, 0, $otherFloor, 0, 0);
            $this->createLinkedMarkers($this->floor, 59, 0, $otherFloor, 1, 0);
            $this->createKillZoneAtCell($dungeonRoute, $this->floor, 0, 0, 1);
            $killZone = $this->createKillZoneAtCell($dungeonRoute, $this->floor, 60, 0, 2);

            // Act
            $withoutCost = app(KillZonePathServiceInterface::class)->findPathsToKillZones($dungeonRoute);
            config(['keystoneguru.walk_graph.floor_switch_cost' => 100.0]);
            $withCost = app(KillZonePathServiceInterface::class)->findPathsToKillZones($dungeonRoute);

            // Assert - 9 yards over the other floor against 180 on this one, until each switch costs 100
            $this->assertCount(6, $withoutCost[$killZone->id]);
            $this->assertSame([
                $this->cellLocation($this->floor, 0, 0),
                $this->cellLocation($this->floor, 60, 0),
            ], $this->toLocations($withCost[$killZone->id]));
        } finally {
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function calculateForRoute_givenFeatureInactive_returnsStraightSegments(): void
    {
        // Arrange
        $this->createCorridorWalkGraph(1);
        $dungeonRoute = $this->createDungeonRoute();
        Feature::deactivate(WalkGraphKillZonePaths::class);

        try {
            $this->createKillZoneAtCell($dungeonRoute, $this->floor, 0, 0, 1);
            $this->createKillZoneAtCell($dungeonRoute, $this->floor, 2, 0, 2);

            // Act
            $result = app(KillZonePathServiceInterface::class)->calculateForRoute($dungeonRoute, false);

            // Assert
            $this->assertSame([[
                $this->cellPoint($this->floor, 0, 0),
                $this->cellPoint($this->floor, 2, 0),
            ]], $this->roundPoints($result));
        } finally {
            Feature::forget(WalkGraphKillZonePaths::class);
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function calculateForRoute_givenFeatureActive_returnsSegmentsAroundTheWall(): void
    {
        // Arrange
        $this->createCorridorWalkGraph(1);
        $dungeonRoute = $this->createDungeonRoute();
        Feature::activate(WalkGraphKillZonePaths::class);

        try {
            $this->createKillZoneAtCell($dungeonRoute, $this->floor, 0, 0, 1);
            $this->createKillZoneAtCell($dungeonRoute, $this->floor, 2, 0, 2);

            // Act
            $result = app(KillZonePathServiceInterface::class)->calculateForRoute($dungeonRoute, false);

            // Assert
            $this->assertSame([[
                $this->cellPoint($this->floor, 0, 0),
                $this->cellPoint($this->floor, 0, 4),
                $this->cellPoint($this->floor, 2, 4),
                $this->cellPoint($this->floor, 2, 0),
            ]], $this->roundPoints($result));
        } finally {
            Feature::forget(WalkGraphKillZonePaths::class);
            $dungeonRoute->delete();
        }
    }

    /**
     * @param list<array{int, int}> $corners cell offsets the corridor runs straight between; the U by default
     */
    private function createCorridorWalkGraph(int $runCount, array $corners = []): void
    {
        $cells = $corners === [] ? self::CORRIDOR : [];
        for ($i = 1; $i < count($corners); $i++) {
            [$fromX, $fromY] = $corners[$i - 1];
            [$toX, $toY]     = $corners[$i];
            $steps           = max(abs($toX - $fromX), abs($toY - $fromY));
            for ($step = $i === 1 ? 0 : 1; $step <= $steps; $step++) {
                $cells[] = [$fromX + intdiv(($toX - $fromX) * $step, $steps), $fromY + intdiv(($toY - $fromY) * $step, $steps)];
            }
        }

        $edgeCounts = [];
        for ($i = 1; $i < count($cells); $i++) {
            $edgeKey = WalkGrid::edgeKey(
                $this->baseCellX + $cells[$i - 1][0],
                $this->baseCellY + $cells[$i - 1][1],
                $this->baseCellX + $cells[$i][0],
                $this->baseCellY + $cells[$i][1],
            );
            $edgeCounts[$edgeKey] = $runCount;
        }

        WalkGraph::factory()->create([
            'ui_map_id' => self::UI_MAP_ID,
            'cell_size' => self::CELL_SIZE,
            'run_count' => $runCount,
            'edges'     => WalkGrid::encodeEdgeCounts($edgeCounts),
        ]);
    }

    private function createOtherFloor(): Floor
    {
        return FloorFactory::new()->create([
            'dungeon_id'   => $this->floor->dungeon_id,
            'index'        => 2,
            'name'         => 'Test Floor 2',
            'default'      => false,
            'ingame_min_x' => $this->floor->ingame_min_x,
            'ingame_max_x' => $this->floor->ingame_max_x,
            'ingame_min_y' => $this->floor->ingame_min_y,
            'ingame_max_y' => $this->floor->ingame_max_y,
        ]);
    }

    private function createDungeonRoute(): DungeonRoute
    {
        return DungeonRoute::factory()->create([
            'dungeon_id'         => $this->floor->dungeon_id,
            'mapping_version_id' => $this->mappingVersion->id,
        ]);
    }

    private function createKillZoneAtCell(DungeonRoute $dungeonRoute, Floor $floor, float $cellX, float $cellY, int $index): KillZone
    {
        $latLng = $this->cellLatLng($floor, $cellX, $cellY);

        return KillZone::factory()->create([
            'dungeon_route_id' => $dungeonRoute->id,
            'floor_id'         => $floor->id,
            'lat'              => $latLng->getLat(),
            'lng'              => $latLng->getLng(),
            'index'            => $index,
        ]);
    }

    /**
     * @return array{DungeonFloorSwitchMarker, DungeonFloorSwitchMarker}
     */
    private function createLinkedMarkers(Floor $floor, float $cellX, float $cellY, Floor $otherFloor, float $otherCellX, float $otherCellY): array
    {
        $marker      = $this->createMarker($floor, $otherFloor, $this->cellLatLng($floor, $cellX, $cellY));
        $otherMarker = $this->createMarker($otherFloor, $floor, $this->cellLatLng($otherFloor, $otherCellX, $otherCellY));
        $marker->update(['linked_dungeon_floor_switch_marker_id' => $otherMarker->id]);
        $otherMarker->update(['linked_dungeon_floor_switch_marker_id' => $marker->id]);

        return [$marker, $otherMarker];
    }

    private function createMarker(Floor $floor, Floor $targetFloor, LatLng $latLng): DungeonFloorSwitchMarker
    {
        return DungeonFloorSwitchMarker::create([
            'mapping_version_id'                    => $this->mappingVersion->id,
            'floor_id'                              => $floor->id,
            'target_floor_id'                       => $targetFloor->id,
            'source_floor_id'                       => null,
            'linked_dungeon_floor_switch_marker_id' => null,
            'direction'                             => null,
            'hidden_in_facade'                      => false,
            'lat'                                   => $latLng->getLat(),
            'lng'                                   => $latLng->getLng(),
        ]);
    }

    private function cellLatLng(Floor $floor, float $cellX, float $cellY): LatLng
    {
        return app(CoordinatesServiceInterface::class)->calculateMapLocationForIngameLocation(new IngameXY(
            ($this->baseCellX + $cellX + 0.5) * self::CELL_SIZE,
            ($this->baseCellY + $cellY + 0.5) * self::CELL_SIZE,
            $floor,
        ));
    }

    /**
     * @return array{int, float, float} [floor id, lat, lng] of the cell centre
     */
    private function cellLocation(Floor $floor, float $cellX, float $cellY): array
    {
        $latLng = $this->cellLatLng($floor, $cellX, $cellY);

        return [$floor->id, round($latLng->getLat(), 4), round($latLng->getLng(), 4)];
    }

    /**
     * @return array{lat: float, lng: float, floor_id: int}
     */
    private function cellPoint(Floor $floor, int $cellX, int $cellY): array
    {
        $latLng = $this->cellLatLng($floor, $cellX, $cellY);

        return ['lat' => round($latLng->getLat(), 4), 'lng' => round($latLng->getLng(), 4), 'floor_id' => $floor->id];
    }

    /**
     * @param array<int, array<int, array{lat: float, lng: float, floor_id: int|null}>> $segments
     *
     * @return array<int, array<int, array{lat: float, lng: float, floor_id: int|null}>>
     */
    private function roundPoints(array $segments): array
    {
        return array_map(static fn(array $segment): array => array_map(static fn(array $point): array => [
            'lat'      => round($point['lat'], 4),
            'lng'      => round($point['lng'], 4),
            'floor_id' => $point['floor_id'],
        ], $segment), $segments);
    }

    /**
     * @param LatLng[] $path
     *
     * @return list<array{int, float, float}>
     */
    private function toLocations(array $path): array
    {
        return array_values(array_map(
            static fn(LatLng $latLng): array => [$latLng->getFloor()->id, round($latLng->getLat(), 4), round($latLng->getLng(), 4)],
            $path,
        ));
    }

    /**
     * @param LatLng[] $path
     */
    private function ingameLength(array $path): float
    {
        $coordinatesService = app(CoordinatesServiceInterface::class);
        $length             = 0.0;
        for ($i = 1; $i < count($path); $i++) {
            $length += $coordinatesService->distanceIngameXY(
                $coordinatesService->calculateIngameLocationForMapLocation($path[$i - 1]),
                $coordinatesService->calculateIngameLocationForMapLocation($path[$i]),
            );
        }

        return $length;
    }
}
