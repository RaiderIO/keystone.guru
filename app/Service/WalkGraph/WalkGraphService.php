<?php

namespace App\Service\WalkGraph;

use App\Logic\Structs\IngameXY;
use App\Logic\Structs\LatLng;
use App\Logic\WalkGraph\WalkGrid;
use App\Logic\WalkGraph\WalkGridArea;
use App\Logic\WalkGraph\WalkGridGraph;
use App\Logic\WalkGraph\WalkGridSegments;
use App\Models\DungeonFloorSwitchMarker;
use App\Models\Enemy;
use App\Models\Floor\Floor;
use App\Models\Floor\FloorUnion;
use App\Models\Mapping\MappingVersion;
use App\Models\WalkGraph\WalkGraph;
use App\Models\WalkGraph\WalkGraphSource;
use App\Repositories\Interfaces\CombatLog\CombatLogWalkGraphContributionRepositoryInterface;
use App\Repositories\Interfaces\Mapping\MappingVersionRepositoryInterface;
use App\Repositories\Interfaces\WalkGraph\EnemyPackFloorSwitchWalkDistanceRepositoryInterface;
use App\Repositories\Interfaces\WalkGraph\EnemyPackWalkDistanceRepositoryInterface;
use App\Repositories\Interfaces\WalkGraph\FloorSwitchMarkerWalkDistanceRepositoryInterface;
use App\Repositories\Interfaces\WalkGraph\WalkGraphRepositoryInterface;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\WalkGraph\Logging\WalkGraphServiceLoggingInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class WalkGraphService implements WalkGraphServiceInterface
{
    /**
     * Decoding and building a Blinding Vale sized graph (~15k nodes, ~26k edges) takes ~25 ms: too slow per route,
     * cheap enough once per request or job that it needs no cache entry. The binding is scoped, so this never
     * outlives one.
     *
     * @var array<int, array{builtAt: int, gridGraph: WalkGridGraph|null}> walk graph id => its last build's grid graph
     */
    private array $gridGraphs = [];

    public function __construct(
        private readonly CombatLogWalkGraphContributionRepositoryInterface   $combatLogWalkGraphContributionRepository,
        private readonly WalkGraphRepositoryInterface                        $walkGraphRepository,
        private readonly EnemyPackWalkDistanceRepositoryInterface            $enemyPackWalkDistanceRepository,
        private readonly EnemyPackFloorSwitchWalkDistanceRepositoryInterface $enemyPackFloorSwitchWalkDistanceRepository,
        private readonly FloorSwitchMarkerWalkDistanceRepositoryInterface    $floorSwitchMarkerWalkDistanceRepository,
        private readonly MappingVersionRepositoryInterface                   $mappingVersionRepository,
        private readonly CoordinatesServiceInterface                         $coordinatesService,
        private readonly CacheServiceInterface                               $cacheService,
        private readonly WalkGraphServiceLoggingInterface                    $log,
    ) {
    }

    public function buildAll(): array
    {
        $graphCount = 0;
        foreach ($this->combatLogWalkGraphContributionRepository->getUiMapIds() as $uiMapId) {
            if ($this->buildGraph($uiMapId) !== null) {
                $graphCount++;
            }
        }

        return [
            'graphs'    => $graphCount,
            'distances' => $this->calculateAllPackWalkDistances(),
        ];
    }

    public function calculateAllPackWalkDistances(): int
    {
        $uiMapIds = $this->walkGraphRepository->getUiMapIds();
        if ($uiMapIds->isEmpty()) {
            return 0;
        }

        /** @var Collection<int, Floor> $floors */
        $floors = Floor::query()
            ->whereIn('ui_map_id', $uiMapIds)
            ->get();

        $distanceCount = 0;
        foreach ($floors->groupBy('dungeon_id') as $dungeonId => $dungeonFloors) {
            foreach ($this->mappingVersionRepository->getNewestPerGameVersion((int)$dungeonId) as $mappingVersion) {
                foreach ($dungeonFloors as $floor) {
                    $distanceCount += $this->calculatePackWalkDistances($mappingVersion, $floor);
                }
            }
        }

        return $distanceCount;
    }

    public function buildGraph(int $uiMapId): ?WalkGraph
    {
        // Anything ingested from here on is newer than the window read below and must survive the prune
        $latestContributionId = $this->combatLogWalkGraphContributionRepository->getLatestId($uiMapId);

        $runKeys = $this->combatLogWalkGraphContributionRepository->getRecentRunKeys(
            $uiMapId,
            (int)config('keystoneguru.walk_graph.run_window'),
        );

        if ($latestContributionId === null || $runKeys->isEmpty()) {
            $this->log->buildGraphNoRuns($uiMapId);

            return null;
        }

        /** @var array<string, array<int, true>> $edgesByRunKey */
        $edgesByRunKey = [];
        foreach ($this->combatLogWalkGraphContributionRepository->getEdgesForRunKeys($uiMapId, $runKeys) as $contribution) {
            foreach (WalkGrid::decodeEdgeKeys($contribution->edges) as $edgeKey) {
                $edgesByRunKey[$contribution->run_key][$edgeKey] = true;
            }
        }

        // An edge counts once per run that walked it, however many of the run's files did
        $edgeCounts = [];
        foreach ($edgesByRunKey as $edges) {
            foreach ($edges as $edgeKey => $true) {
                $edgeCounts[$edgeKey] = ($edgeCounts[$edgeKey] ?? 0) + 1;
            }
        }

        $runCount      = count($edgesByRunKey);
        $minRuns       = (int)config('keystoneguru.walk_graph.min_runs');
        $existingGraph = $this->walkGraphRepository->findByUiMapId($uiMapId);
        if ($existingGraph?->source === WalkGraphSource::Seeder && $runCount < $minRuns) {
            $prunedContributionCount = $this->combatLogWalkGraphContributionRepository->pruneOutsideRunKeys(
                $uiMapId,
                $runKeys,
                $latestContributionId,
            );

            $this->log->buildGraphKeptSeededGraph($uiMapId, $runCount, $existingGraph->run_count, $minRuns, $prunedContributionCount);

            return null;
        }

        $cellSize      = (float)config('keystoneguru.walk_graph.cell_size');
        $walkGridGraph = new WalkGridGraph($cellSize, $edgeCounts);

        $walkGraph = $this->walkGraphRepository->saveForUiMapId($uiMapId, [
            'source'                       => WalkGraphSource::CombatLog,
            'cell_size'                    => $cellSize,
            'run_count'                    => $runCount,
            'node_count'                   => $walkGridGraph->getNodeCount(),
            'edge_count'                   => $walkGridGraph->getEdgeCount(),
            'largest_component_node_count' => $walkGridGraph->getLargestComponentNodeCount(),
            'edges'                        => WalkGrid::encodeEdgeCounts($edgeCounts),
            'built_at'                     => Carbon::now(),
        ]);

        $prunedContributionCount = $this->combatLogWalkGraphContributionRepository->pruneOutsideRunKeys(
            $uiMapId,
            $runKeys,
            $latestContributionId,
        );

        $this->log->buildGraphBuilt(
            $uiMapId,
            $walkGraph->run_count,
            $walkGraph->node_count,
            $walkGraph->edge_count,
            $walkGraph->largest_component_node_count,
            $prunedContributionCount,
        );

        return $walkGraph;
    }

    public function getGridGraph(WalkGraph $walkGraph): WalkGridGraph
    {
        return new WalkGridGraph($walkGraph->cell_size, WalkGrid::decodeEdgeCounts($walkGraph->edges));
    }

    public function getGridGraphForUiMapId(int $uiMapId): ?WalkGridGraph
    {
        $walkGraph = $this->walkGraphRepository->findSummaryByUiMapId($uiMapId);
        if ($walkGraph === null || $walkGraph->run_count < (int)config('keystoneguru.walk_graph.min_runs')) {
            return null;
        }

        $builtAt = $walkGraph->built_at->getTimestamp();
        if (($this->gridGraphs[$walkGraph->id]['builtAt'] ?? null) !== $builtAt) {
            $fullWalkGraph = $this->walkGraphRepository->find($walkGraph->id);

            $this->gridGraphs[$walkGraph->id] = [
                'builtAt'   => $builtAt,
                'gridGraph' => $fullWalkGraph === null ? null : $this->getGridGraph($fullWalkGraph),
            ];
        }

        return $this->gridGraphs[$walkGraph->id]['gridGraph'];
    }

    public function getFloorSegments(Floor $floor): array
    {
        $empty = [
            'run_count'     => 0,
            'min_runs'      => (int)config('keystoneguru.walk_graph.min_runs'),
            'max_run_count' => 0,
            'segments'      => [],
        ];

        $walkGraph = $this->findFloorWalkGraph($floor);
        if ($walkGraph === null) {
            return $empty;
        }

        return $this->cacheService->remember(
            sprintf('walk_graph:%d:%d:floor:%d', $walkGraph->id, $walkGraph->built_at->getTimestamp(), $floor->id),
            fn(): array => $this->buildFloorSegments($walkGraph, $floor, $empty),
            '1 day',
        );
    }

    public function getFloorArea(Floor $floor): array
    {
        $empty = [
            'run_count' => 0,
            'min_runs'  => (int)config('keystoneguru.walk_graph.min_runs'),
            'rings'     => [],
        ];

        $walkGraph = $this->findFloorWalkGraph($floor);
        if ($walkGraph === null) {
            return $empty;
        }

        return $this->cacheService->remember(
            sprintf('walk_graph:%d:%d:floor:%d:area', $walkGraph->id, $walkGraph->built_at->getTimestamp(), $floor->id),
            fn(): array => $this->buildFloorArea($walkGraph, $floor, $empty),
            '1 day',
        );
    }

    public function getFacadeFloorArea(MappingVersion $mappingVersion, Floor $facadeFloor): array
    {
        $empty = [
            'run_count' => 0,
            'min_runs'  => (int)config('keystoneguru.walk_graph.min_runs'),
            'rings'     => [],
        ];

        if (!$facadeFloor->facade || !$mappingVersion->facade_enabled) {
            return $empty;
        }

        /** @var array<int, array{floor: Floor, walkGraph: WalkGraph}> $graphedFloors floor id => the floor and its walk graph */
        $graphedFloors = [];
        foreach ($mappingVersion->getFloorUnionsOnFloor($facadeFloor->id) as $floorUnion) {
            $floor     = $floorUnion->targetFloor;
            $walkGraph = isset($graphedFloors[$floor->id]) ? null : $this->findFloorWalkGraph($floor);
            if ($walkGraph !== null) {
                $graphedFloors[$floor->id] = ['floor' => $floor, 'walkGraph' => $walkGraph];
            }
        }

        if ($graphedFloors === []) {
            return $empty;
        }

        ksort($graphedFloors);

        $graphBuilds = array_map(
            static fn(array $graphedFloor): string => sprintf(
                '%d:%d:%d',
                $graphedFloor['floor']->id,
                $graphedFloor['walkGraph']->id,
                $graphedFloor['walkGraph']->built_at->getTimestamp(),
            ),
            $graphedFloors,
        );

        return $this->cacheService->remember(
            sprintf('walk_graph:facade:%d:floor:%d:area:%s', $mappingVersion->id, $facadeFloor->id, md5(implode(',', $graphBuilds))),
            fn(): array => $this->buildFacadeFloorArea($mappingVersion, array_column($graphedFloors, 'floor'), $empty),
            '1 day',
        );
    }

    public function calculatePackWalkDistances(MappingVersion $mappingVersion, Floor $floor): int
    {
        $minRuns   = (int)config('keystoneguru.walk_graph.min_runs');
        $walkGraph = $floor->ui_map_id === null ? null : $this->walkGraphRepository->findByUiMapId($floor->ui_map_id);
        if ($walkGraph === null || $walkGraph->run_count < $minRuns) {
            $this->log->calculatePackWalkDistancesTooFewRuns($mappingVersion->id, $floor->id, $walkGraph?->run_count, $minRuns);
            $this->enemyPackWalkDistanceRepository->replaceForFloor($mappingVersion->id, $floor->id, []);
            $this->enemyPackFloorSwitchWalkDistanceRepository->replaceForFloor($mappingVersion->id, $floor->id, []);
            $this->floorSwitchMarkerWalkDistanceRepository->replaceForFloor($mappingVersion->id, $floor->id, []);

            return 0;
        }

        $snapRadius      = (float)config('keystoneguru.walk_graph.snap_radius');
        $maxPairDistance = (float)config('keystoneguru.walk_graph.max_pair_distance');
        $maxWalkDistance = (float)config('keystoneguru.walk_graph.max_walk_distance');
        $walkGridGraph   = $this->getGridGraph($walkGraph);

        $packs   = $this->snapPoints($walkGridGraph, $this->getPackCentroids($mappingVersion, $floor), $snapRadius);
        $markers = $floor->facade ?
            [] :
            $this->snapPoints($walkGridGraph, $this->getFloorSwitchMarkerPositions($mappingVersion, $floor), $snapRadius);

        $packPairRows          = [];
        $packMarkerRows        = [];
        $walkedPackPairCount   = 0;
        $walkedPackMarkerCount = 0;
        foreach ($packs as $enemyPackId => $pack) {
            $nodeDistances = $this->getNodeDistances($walkGridGraph, $pack, $maxWalkDistance);

            foreach ($packs as $otherEnemyPackId => $otherPack) {
                if ($otherEnemyPackId <= $enemyPackId) {
                    continue;
                }

                $straightDistance = hypot($otherPack['x'] - $pack['x'], $otherPack['y'] - $pack['y']);
                if ($straightDistance > $maxPairDistance) {
                    continue;
                }

                $walkDistance = $this->getWalkDistance($pack, $nodeDistances, $otherPack, $straightDistance);
                if ($walkDistance !== null) {
                    $walkedPackPairCount++;
                }

                $packPairRows[] = [
                    'enemy_pack_id'       => $enemyPackId,
                    'other_enemy_pack_id' => $otherEnemyPackId,
                    'straight_distance'   => round($straightDistance, 2),
                    'walk_distance'       => $walkDistance === null ? null : round($walkDistance, 2),
                ];
            }

            // A floor switch is worth knowing about at any range, so markers are not held to the maximum pair distance
            foreach ($markers as $dungeonFloorSwitchMarkerId => $marker) {
                $straightDistance = hypot($marker['x'] - $pack['x'], $marker['y'] - $pack['y']);
                $walkDistance     = $this->getWalkDistance($pack, $nodeDistances, $marker, $straightDistance);
                if ($walkDistance !== null) {
                    $walkedPackMarkerCount++;
                }

                $packMarkerRows[] = [
                    'enemy_pack_id'                  => $enemyPackId,
                    'dungeon_floor_switch_marker_id' => $dungeonFloorSwitchMarkerId,
                    'straight_distance'              => round($straightDistance, 2),
                    'walk_distance'                  => $walkDistance === null ? null : round($walkDistance, 2),
                ];
            }
        }

        $markerPairRows        = [];
        $walkedMarkerPairCount = 0;
        foreach ($markers as $dungeonFloorSwitchMarkerId => $marker) {
            $nodeDistances = null;

            foreach ($markers as $otherDungeonFloorSwitchMarkerId => $otherMarker) {
                if ($otherDungeonFloorSwitchMarkerId <= $dungeonFloorSwitchMarkerId) {
                    continue;
                }

                $nodeDistances ??= $this->getNodeDistances($walkGridGraph, $marker, $maxWalkDistance);
                $straightDistance = hypot($otherMarker['x'] - $marker['x'], $otherMarker['y'] - $marker['y']);
                $walkDistance     = $this->getWalkDistance($marker, $nodeDistances, $otherMarker, $straightDistance);
                if ($walkDistance !== null) {
                    $walkedMarkerPairCount++;
                }

                $markerPairRows[] = [
                    'dungeon_floor_switch_marker_id'       => $dungeonFloorSwitchMarkerId,
                    'other_dungeon_floor_switch_marker_id' => $otherDungeonFloorSwitchMarkerId,
                    'straight_distance'                    => round($straightDistance, 2),
                    'walk_distance'                        => $walkDistance === null ? null : round($walkDistance, 2),
                ];
            }
        }

        $this->enemyPackWalkDistanceRepository->replaceForFloor($mappingVersion->id, $floor->id, $packPairRows);
        $this->enemyPackFloorSwitchWalkDistanceRepository->replaceForFloor($mappingVersion->id, $floor->id, $packMarkerRows);
        $this->floorSwitchMarkerWalkDistanceRepository->replaceForFloor($mappingVersion->id, $floor->id, $markerPairRows);

        $this->log->calculatePackWalkDistancesCalculated(
            $mappingVersion->id,
            $floor->id,
            count($packs),
            count(array_filter($packs, static fn(array $pack) => $pack['node'] !== null)),
            count($packPairRows),
            $walkedPackPairCount,
            count($markers),
            count(array_filter($markers, static fn(array $marker) => $marker['node'] !== null)),
            count($packMarkerRows),
            $walkedPackMarkerCount,
            count($markerPairRows),
            $walkedMarkerPairCount,
        );

        return count($packPairRows);
    }

    /**
     * The ui map's walk graph, when the floor can be drawn on: not a facade, and with a ui map and in-game bounds.
     */
    private function findFloorWalkGraph(Floor $floor): ?WalkGraph
    {
        $hasIngameBounds = (int)($floor->ingame_max_x - $floor->ingame_min_x) !== 0 &&
            (int)($floor->ingame_max_y - $floor->ingame_min_y) !== 0;
        if ($floor->facade || $floor->ui_map_id === null || !$hasIngameBounds) {
            return null;
        }

        return $this->walkGraphRepository->findByUiMapId($floor->ui_map_id);
    }

    /**
     * The graph's edges with both ends inside the floor's in-game bounds, since floors that share a ui map each cover
     * part of it.
     *
     * @return array<int, int> edge key => run count
     */
    private function getFloorEdgeCounts(WalkGraph $walkGraph, Floor $floor): array
    {
        $minX     = min($floor->ingame_min_x, $floor->ingame_max_x);
        $maxX     = max($floor->ingame_min_x, $floor->ingame_max_x);
        $minY     = min($floor->ingame_min_y, $floor->ingame_max_y);
        $maxY     = max($floor->ingame_min_y, $floor->ingame_max_y);
        $cellSize = $walkGraph->cell_size;

        $isCellOnFloor = static function (int $cellX, int $cellY) use ($minX, $maxX, $minY, $maxY, $cellSize): bool {
            $x = ($cellX + 0.5) * $cellSize;
            $y = ($cellY + 0.5) * $cellSize;

            return $x >= $minX && $x <= $maxX && $y >= $minY && $y <= $maxY;
        };

        $edgeCounts = [];
        foreach (WalkGrid::decodeEdgeCounts($walkGraph->edges) as $edgeKey => $runCount) {
            [$cellX, $cellY, $otherCellX, $otherCellY] = WalkGrid::decodeEdgeKey($edgeKey);
            if ($isCellOnFloor($cellX, $cellY) && $isCellOnFloor($otherCellX, $otherCellY)) {
                $edgeCounts[$edgeKey] = $runCount;
            }
        }

        return $edgeCounts;
    }

    /**
     * @param array{run_count: int, min_runs: int, rings: list<list<array{float, float}>>} $empty
     *
     * @return array{run_count: int, min_runs: int, rings: list<list<array{float, float}>>}
     */
    private function buildFloorArea(WalkGraph $walkGraph, Floor $floor, array $empty): array
    {
        $walkedCells = [];
        foreach (array_keys($this->getFloorEdgeCounts($walkGraph, $floor)) as $edgeKey) {
            [$cellX, $cellY, $otherCellX, $otherCellY] = WalkGrid::decodeEdgeKey($edgeKey);

            $walkedCells[WalkGrid::cellKey($cellX, $cellY)]           = [$cellX, $cellY];
            $walkedCells[WalkGrid::cellKey($otherCellX, $otherCellY)] = [$otherCellX, $otherCellY];
        }

        $rings = WalkGridArea::outline(
            array_values($walkedCells),
            (int)config('keystoneguru.walk_graph.area.grow_cells'),
            (int)config('keystoneguru.walk_graph.area.close_cells'),
            (int)config('keystoneguru.walk_graph.area.min_region_cells'),
            (float)config('keystoneguru.walk_graph.area.simplify_tolerance'),
            (int)config('keystoneguru.walk_graph.area.smooth_iterations'),
        );

        $cellSize = $walkGraph->cell_size;
        $mapRings = [];
        foreach ($rings as $ring) {
            $mapRing = [];
            foreach ($ring as [$cornerX, $cornerY]) {
                $latLng = $this->coordinatesService->calculateMapLocationForIngameLocation(
                    new IngameXY($cornerX * $cellSize, $cornerY * $cellSize, $floor),
                );

                $mapRing[] = [round($latLng->getLat(), 2), round($latLng->getLng(), 2)];
            }

            $mapRings[] = $mapRing;
        }

        return array_merge($empty, [
            'run_count' => $walkGraph->run_count,
            'rings'     => $mapRings,
        ]);
    }

    /**
     * Every floor's area, moved onto the facade through its floor unions. Each ring is moved as a whole, by the floor
     * union most of its corners fall in, so a ring never tears apart where union areas meet; a ring with no corner in
     * any union area is not on the facade and is left out.
     *
     * The rings of one floor wind one way around the area and the other way around its holes, but a floor whose in-game
     * axes run the other way mirrors its rings. Every floor's rings are turned to wind the same way, so the areas of
     * overlapping floors add up under the non-zero fill rule instead of cutting holes into each other.
     *
     * @param list<Floor>                                                                  $floors Floors that have a walk graph
     * @param array{run_count: int, min_runs: int, rings: list<list<array{float, float}>>} $empty
     *
     * @return array{run_count: int, min_runs: int, rings: list<list<array{float, float}>>}
     */
    private function buildFacadeFloorArea(MappingVersion $mappingVersion, array $floors, array $empty): array
    {
        $runCount    = null;
        $facadeRings = [];
        foreach ($floors as $floor) {
            $floorArea = $this->getFloorArea($floor);
            if ($floorArea['rings'] === []) {
                continue;
            }

            // The least complete graph decides whether the facade's area is shown as incomplete
            $runCount = min($runCount ?? PHP_INT_MAX, $floorArea['run_count']);

            $floorRings      = [];
            $largestRingArea = 0.0;
            foreach ($floorArea['rings'] as $ring) {
                $facadeRing = $this->convertRingToFacade($mappingVersion, $floor, $ring);
                if ($facadeRing === null) {
                    continue;
                }

                $ringArea = self::getSignedRingArea($facadeRing);
                if (abs($ringArea) > abs($largestRingArea)) {
                    $largestRingArea = $ringArea;
                }

                $floorRings[] = $facadeRing;
            }

            // The largest ring is always an outline, never a hole: make it wind counter-clockwise
            foreach ($floorRings as $floorRing) {
                $facadeRings[] = $largestRingArea < 0 ? array_reverse($floorRing) : $floorRing;
            }
        }

        if ($facadeRings === []) {
            return $empty;
        }

        return array_merge($empty, [
            'run_count' => $runCount,
            'rings'     => $facadeRings,
        ]);
    }

    /**
     * @param list<array{float, float}> $ring [lat, lng] on the floor
     *
     * @return list<array{float, float}>|null [lat, lng] on the facade, null when the ring is not on it
     */
    private function convertRingToFacade(MappingVersion $mappingVersion, Floor $floor, array $ring): ?array
    {
        $floorUnions = $mappingVersion->getFloorUnionsForFloor($floor);
        if ($floorUnions->count() === 1) {
            $floorUnion = $floorUnions->first();
        } else {
            /** @var array<int, int> $cornerCounts floor union id => corners inside its areas */
            $cornerCounts = [];
            foreach ($ring as [$lat, $lng]) {
                $cornerFloorUnion = $mappingVersion->getFloorUnionForLatLng($this->coordinatesService, new LatLng($lat, $lng, $floor));
                if ($cornerFloorUnion !== null) {
                    $cornerCounts[$cornerFloorUnion->id] = ($cornerCounts[$cornerFloorUnion->id] ?? 0) + 1;
                }
            }

            if ($cornerCounts === []) {
                return null;
            }

            arsort($cornerCounts);
            /** @var FloorUnion $floorUnion */
            $floorUnion = $floorUnions->firstWhere('id', array_key_first($cornerCounts));
        }

        $facadeRing = [];
        foreach ($ring as [$lat, $lng]) {
            $facadeLatLng = $this->coordinatesService->convertMapLocationToFacadeMapLocation(
                $mappingVersion,
                new LatLng($lat, $lng, $floor),
                $floorUnion,
            );

            $facadeRing[] = [round($facadeLatLng->getLat(), 2), round($facadeLatLng->getLng(), 2)];
        }

        return $facadeRing;
    }

    /**
     * @param array{run_count: int, min_runs: int, max_run_count: int, segments: list<array{int, float, float, float, float}>} $empty
     *
     * @return array{run_count: int, min_runs: int, max_run_count: int, segments: list<array{int, float, float, float, float}>}
     */
    private function buildFloorSegments(WalkGraph $walkGraph, Floor $floor, array $empty): array
    {
        $cellSize   = $walkGraph->cell_size;
        $edgeCounts = $this->getFloorEdgeCounts($walkGraph, $floor);

        $segments    = [];
        $maxRunCount = 0;
        foreach (WalkGridSegments::merge($edgeCounts) as [$fromCellX, $fromCellY, $toCellX, $toCellY, $runCount]) {
            $from = $this->coordinatesService->calculateMapLocationForIngameLocation(
                new IngameXY(($fromCellX + 0.5) * $cellSize, ($fromCellY + 0.5) * $cellSize, $floor),
            );
            $to = $this->coordinatesService->calculateMapLocationForIngameLocation(
                new IngameXY(($toCellX + 0.5) * $cellSize, ($toCellY + 0.5) * $cellSize, $floor),
            );

            $segments[] = [
                $runCount,
                round($from->getLat(), 2),
                round($from->getLng(), 2),
                round($to->getLat(), 2),
                round($to->getLng(), 2),
            ];
            $maxRunCount = max($maxRunCount, $runCount);
        }

        return array_merge($empty, [
            'run_count'     => $walkGraph->run_count,
            'max_run_count' => $maxRunCount,
            'segments'      => $segments,
        ]);
    }

    /**
     * Snaps each point onto the walk graph, sorted by id.
     *
     * @param array<int, array{float, float}> $points id => in-game [x, y]
     *
     * @return array<int, array{x: float, y: float, node: int|null, snapDistance: float}>
     */
    private function snapPoints(WalkGridGraph $walkGridGraph, array $points, float $snapRadius): array
    {
        $snappedPoints = [];
        foreach ($points as $id => [$x, $y]) {
            $node         = $walkGridGraph->snap($x, $y, $snapRadius);
            $snapDistance = 0.0;
            if ($node !== null) {
                [$nodeX, $nodeY] = $walkGridGraph->getNodePosition($node);
                $snapDistance    = hypot($nodeX - $x, $nodeY - $y);
            }

            $snappedPoints[$id] = ['x' => $x, 'y' => $y, 'node' => $node, 'snapDistance' => $snapDistance];
        }

        ksort($snappedPoints);

        return $snappedPoints;
    }

    /**
     * @param array{x: float, y: float, node: int|null, snapDistance: float} $point
     *
     * @return array<int, float> node => walking distance from the point's node
     */
    private function getNodeDistances(WalkGridGraph $walkGridGraph, array $point, float $maxWalkDistance): array
    {
        return $point['node'] === null ? [] : $walkGridGraph->getDistancesFrom($point['node'], $maxWalkDistance);
    }

    /**
     * @param array{x: float, y: float, node: int|null, snapDistance: float} $from
     * @param array<int, float>                                              $nodeDistances node => walking distance from $from's node
     * @param array{x: float, y: float, node: int|null, snapDistance: float} $to
     *
     * @return float|null null when the walk graph cannot connect the two points
     */
    private function getWalkDistance(array $from, array $nodeDistances, array $to, float $straightDistance): ?float
    {
        if ($to['node'] === null || !isset($nodeDistances[$to['node']])) {
            return null;
        }

        // The snap offsets are walked too; walking is never shorter than the straight line
        return max($straightDistance, $from['snapDistance'] + $nodeDistances[$to['node']] + $to['snapDistance']);
    }

    /**
     * The in-game position of each floor switch marker on the floor.
     *
     * @return array<int, array{float, float}> dungeon floor switch marker id => [x, y]
     */
    private function getFloorSwitchMarkerPositions(MappingVersion $mappingVersion, Floor $floor): array
    {
        /** @var Collection<int, DungeonFloorSwitchMarker> $dungeonFloorSwitchMarkers */
        $dungeonFloorSwitchMarkers = DungeonFloorSwitchMarker::query()
            ->where('mapping_version_id', $mappingVersion->id)
            ->where('floor_id', $floor->id)
            ->get(['id', 'lat', 'lng']);

        $positions = [];
        foreach ($dungeonFloorSwitchMarkers as $dungeonFloorSwitchMarker) {
            $ingameXY = $this->coordinatesService->calculateIngameLocationForMapLocation(
                new LatLng($dungeonFloorSwitchMarker->lat, $dungeonFloorSwitchMarker->lng, $floor),
            );

            $positions[$dungeonFloorSwitchMarker->id] = [$ingameXY->getX(), $ingameXY->getY()];
        }

        return $positions;
    }

    /**
     * The in-game centroid of each pack's enemies on the floor.
     *
     * @return array<int, array{float, float}> enemy pack id => [x, y]
     */
    private function getPackCentroids(MappingVersion $mappingVersion, Floor $floor): array
    {
        /** @var Collection<int, Enemy> $enemies */
        $enemies = Enemy::query()
            ->where('mapping_version_id', $mappingVersion->id)
            ->where('floor_id', $floor->id)
            ->whereNotNull('enemy_pack_id')
            ->get(['id', 'enemy_pack_id', 'lat', 'lng']);

        $centroids = [];
        foreach ($enemies->groupBy('enemy_pack_id') as $enemyPackId => $packEnemies) {
            $sumX = 0.0;
            $sumY = 0.0;
            foreach ($packEnemies as $enemy) {
                $ingameXY = $this->coordinatesService->calculateIngameLocationForMapLocation(
                    new LatLng($enemy->lat, $enemy->lng, $floor),
                );
                $sumX += $ingameXY->getX();
                $sumY += $ingameXY->getY();
            }

            $centroids[(int)$enemyPackId] = [$sumX / $packEnemies->count(), $sumY / $packEnemies->count()];
        }

        return $centroids;
    }

    /**
     * The shoelace formula, with lng as x and lat as y: positive when the ring winds counter-clockwise.
     *
     * @param list<array{float, float}> $ring [lat, lng]
     */
    private static function getSignedRingArea(array $ring): float
    {
        $area  = 0.0;
        $count = count($ring);
        for ($i = 0; $i < $count; $i++) {
            [$lat, $lng]         = $ring[$i];
            [$nextLat, $nextLng] = $ring[($i + 1) % $count];

            $area += $lng * $nextLat - $nextLng * $lat;
        }

        return $area / 2;
    }
}
