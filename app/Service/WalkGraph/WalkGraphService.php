<?php

namespace App\Service\WalkGraph;

use App\Logic\Structs\IngameXY;
use App\Logic\Structs\LatLng;
use App\Logic\WalkGraph\WalkGrid;
use App\Logic\WalkGraph\WalkGridGraph;
use App\Logic\WalkGraph\WalkGridSegments;
use App\Models\Enemy;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Models\WalkGraph\WalkGraph;
use App\Models\WalkGraph\WalkGraphSource;
use App\Repositories\Interfaces\CombatLog\CombatLogWalkGraphContributionRepositoryInterface;
use App\Repositories\Interfaces\Mapping\MappingVersionRepositoryInterface;
use App\Repositories\Interfaces\WalkGraph\EnemyPackWalkDistanceRepositoryInterface;
use App\Repositories\Interfaces\WalkGraph\WalkGraphRepositoryInterface;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\WalkGraph\Logging\WalkGraphServiceLoggingInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class WalkGraphService implements WalkGraphServiceInterface
{
    public function __construct(
        private readonly CombatLogWalkGraphContributionRepositoryInterface $combatLogWalkGraphContributionRepository,
        private readonly WalkGraphRepositoryInterface                      $walkGraphRepository,
        private readonly EnemyPackWalkDistanceRepositoryInterface          $enemyPackWalkDistanceRepository,
        private readonly MappingVersionRepositoryInterface                 $mappingVersionRepository,
        private readonly CoordinatesServiceInterface                       $coordinatesService,
        private readonly CacheServiceInterface                             $cacheService,
        private readonly WalkGraphServiceLoggingInterface                  $log,
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

    public function getFloorSegments(Floor $floor): array
    {
        $empty = [
            'run_count'     => 0,
            'min_runs'      => (int)config('keystoneguru.walk_graph.min_runs'),
            'max_run_count' => 0,
            'segments'      => [],
        ];

        $hasIngameBounds = (int)($floor->ingame_max_x - $floor->ingame_min_x) !== 0 &&
            (int)($floor->ingame_max_y - $floor->ingame_min_y) !== 0;
        if ($floor->facade || $floor->ui_map_id === null || !$hasIngameBounds) {
            return $empty;
        }

        $walkGraph = $this->walkGraphRepository->findByUiMapId($floor->ui_map_id);
        if ($walkGraph === null) {
            return $empty;
        }

        return $this->cacheService->remember(
            sprintf('walk_graph:%d:%d:floor:%d', $walkGraph->id, $walkGraph->built_at->getTimestamp(), $floor->id),
            fn(): array => $this->buildFloorSegments($walkGraph, $floor, $empty),
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

            return 0;
        }

        $snapRadius      = (float)config('keystoneguru.walk_graph.snap_radius');
        $maxPairDistance = (float)config('keystoneguru.walk_graph.max_pair_distance');
        $maxWalkDistance = (float)config('keystoneguru.walk_graph.max_walk_distance');
        $walkGridGraph   = $this->getGridGraph($walkGraph);

        /** @var array<int, array{x: float, y: float, node: int|null, snapDistance: float}> $packs */
        $packs = [];
        foreach ($this->getPackCentroids($mappingVersion, $floor) as $enemyPackId => [$x, $y]) {
            $node         = $walkGridGraph->snap($x, $y, $snapRadius);
            $snapDistance = 0.0;
            if ($node !== null) {
                [$nodeX, $nodeY] = $walkGridGraph->getNodePosition($node);
                $snapDistance    = hypot($nodeX - $x, $nodeY - $y);
            }

            $packs[$enemyPackId] = ['x' => $x, 'y' => $y, 'node' => $node, 'snapDistance' => $snapDistance];
        }

        ksort($packs);

        $rows            = [];
        $walkedPairCount = 0;
        foreach ($packs as $enemyPackId => $pack) {
            $nodeDistances = $pack['node'] === null ?
                [] :
                $walkGridGraph->getDistancesFrom($pack['node'], $maxWalkDistance);

            foreach ($packs as $otherEnemyPackId => $otherPack) {
                if ($otherEnemyPackId <= $enemyPackId) {
                    continue;
                }

                $straightDistance = hypot($otherPack['x'] - $pack['x'], $otherPack['y'] - $pack['y']);
                if ($straightDistance > $maxPairDistance) {
                    continue;
                }

                $walkDistance = null;
                if ($otherPack['node'] !== null && isset($nodeDistances[$otherPack['node']])) {
                    // The snap offsets are walked too; walking is never shorter than the straight line
                    $walkDistance = max(
                        $straightDistance,
                        $pack['snapDistance'] + $nodeDistances[$otherPack['node']] + $otherPack['snapDistance'],
                    );
                    $walkedPairCount++;
                }

                $rows[] = [
                    'enemy_pack_id'       => $enemyPackId,
                    'other_enemy_pack_id' => $otherEnemyPackId,
                    'straight_distance'   => round($straightDistance, 2),
                    'walk_distance'       => $walkDistance === null ? null : round($walkDistance, 2),
                ];
            }
        }

        $this->enemyPackWalkDistanceRepository->replaceForFloor($mappingVersion->id, $floor->id, $rows);

        $this->log->calculatePackWalkDistancesCalculated(
            $mappingVersion->id,
            $floor->id,
            count($packs),
            count(array_filter($packs, static fn(array $pack) => $pack['node'] !== null)),
            count($rows),
            $walkedPairCount,
        );

        return count($rows);
    }

    /**
     * @param array{run_count: int, min_runs: int, max_run_count: int, segments: list<array{int, float, float, float, float}>} $empty
     *
     * @return array{run_count: int, min_runs: int, max_run_count: int, segments: list<array{int, float, float, float, float}>}
     */
    private function buildFloorSegments(WalkGraph $walkGraph, Floor $floor, array $empty): array
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
}
