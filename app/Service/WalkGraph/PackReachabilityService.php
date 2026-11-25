<?php

namespace App\Service\WalkGraph;

use App\Logic\Structs\IngameXY;
use App\Logic\Structs\LatLng;
use App\Logic\WalkGraph\WalkGridGraph;
use App\Models\DungeonStart;
use App\Models\Enemy;
use App\Models\EnemyPack;
use App\Models\EnemyPatrol;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Repositories\Interfaces\WalkGraph\WalkGraphRepositoryInterface;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Support\Collection;

class PackReachabilityService implements PackReachabilityServiceInterface
{
    public function __construct(
        private readonly CoordinatesServiceInterface  $coordinatesService,
        private readonly PackDistanceServiceInterface $packDistanceService,
        private readonly WalkGraphServiceInterface    $walkGraphService,
        private readonly WalkGraphRepositoryInterface $walkGraphRepository,
        private readonly CacheServiceInterface        $cacheService,
    ) {
    }

    public function getNeighbours(MappingVersion $mappingVersion, Collection $enemyPacks, Collection $enemies, int $neighbourCount): array
    {
        $centroids         = $this->packDistanceService->getPackCentroids($enemyPacks);
        $plainNeighbours   = $this->packDistanceService->getNeighbours($mappingVersion, $enemyPacks, PHP_INT_MAX);
        $blockingEnemies   = $enemies->filter(self::isAlwaysPresent(...));
        $enemyPacksByFloor = $enemyPacks->filter(static fn(EnemyPack $enemyPack) => isset($centroids[$enemyPack->id]))
            ->groupBy('floor_id');

        $result = [];
        foreach ($enemyPacksByFloor as $floorEnemyPacks) {
            /** @var Floor $floor */
            $floor   = $floorEnemyPacks->first()->floor;
            $sources = [];
            foreach ($floorEnemyPacks as $enemyPack) {
                $sources[$enemyPack->id] = ['position' => $centroids[$enemyPack->id], 'enemyPackId' => $enemyPack->id];
            }

            $reachability = $this->getFloorReachability(
                $floor,
                $sources,
                $this->getTargets($floorEnemyPacks, $centroids),
                $blockingEnemies,
                true,
            );

            foreach ($floorEnemyPacks as $enemyPack) {
                $result[$enemyPack->id] = $this->applyReachability(
                    $plainNeighbours[$enemyPack->id] ?? [],
                    $reachability[$enemyPack->id] ?? null,
                    $neighbourCount,
                );
            }
        }

        return $result;
    }

    public function getOpeningPacks(Collection $dungeonStarts, Collection $enemyPacks, Collection $enemies, int $packCount): array
    {
        $centroids       = $this->packDistanceService->getPackCentroids($enemyPacks);
        $blockingEnemies = $enemies->filter(self::isAlwaysPresent(...));

        $result = [];
        foreach ($dungeonStarts->groupBy('floor_id') as $floorDungeonStarts) {
            /** @var Floor $floor */
            $floor   = $floorDungeonStarts->first()->floor;
            $targets = $this->getTargets($enemyPacks->where('floor_id', $floor->id), $centroids);

            $sources = [];
            foreach ($floorDungeonStarts as $dungeonStart) {
                /** @var DungeonStart $dungeonStart */
                $sources[$dungeonStart->id] = [
                    'position' => $this->coordinatesService->calculateIngameLocationForMapLocation(
                        new LatLng($dungeonStart->lat, $dungeonStart->lng, $floor),
                    ),
                    'enemyPackId' => null,
                ];
            }

            $reachability = $this->getFloorReachability($floor, $sources, $targets, $blockingEnemies, true);

            foreach ($sources as $dungeonStartId => $source) {
                $result[$dungeonStartId] = $this->applyReachability(
                    $this->getStraightLineNeighbours($source['position'], $targets),
                    $reachability[$dungeonStartId] ?? null,
                    $packCount,
                );
            }
        }

        return $result;
    }

    public function getReachablePacksFrom(IngameXY $from, Collection $enemyPacks, Collection $standingEnemies, int $packCount): array
    {
        $floor     = $from->getFloor();
        $centroids = $this->packDistanceService->getPackCentroids($enemyPacks->where('floor_id', $floor->id));
        $targets   = $this->getTargets($enemyPacks->where('floor_id', $floor->id), $centroids);

        $reachability = $this->getFloorReachability(
            $floor,
            ['from' => ['position' => $from, 'enemyPackId' => null]],
            $targets,
            $standingEnemies,
            false,
        );

        return $this->applyReachability(
            $this->getStraightLineNeighbours($from, $targets),
            $reachability['from'] ?? null,
            $packCount,
        );
    }

    /**
     * Seasonal, teeming-only and difficulty-only enemies are not there on every run, so they cannot be relied on to
     * block a walk.
     */
    private static function isAlwaysPresent(Enemy $enemy): bool
    {
        return empty($enemy->seasonal_type) && $enemy->teeming === null && $enemy->dungeon_difficulty === null;
    }

    /**
     * @param  Collection<int, EnemyPack> $enemyPacks
     * @param  array<int, IngameXY>       $centroids  enemy pack id => centroid
     * @return array<int, IngameXY>       enemy pack id => centroid, of the packs that have one
     */
    private function getTargets(Collection $enemyPacks, array $centroids): array
    {
        $targets = [];
        foreach ($enemyPacks as $enemyPack) {
            if (isset($centroids[$enemyPack->id])) {
                $targets[$enemyPack->id] = $centroids[$enemyPack->id];
            }
        }

        ksort($targets);

        return $targets;
    }

    /**
     * @param  array<int, IngameXY>                                               $targets enemy pack id => centroid
     * @return list<array{enemy_pack_id: int, distance: float, unverified: bool}>
     */
    private function getStraightLineNeighbours(IngameXY $from, array $targets): array
    {
        $neighbours = [];
        foreach ($targets as $enemyPackId => $centroid) {
            $neighbours[] = [
                'enemy_pack_id' => $enemyPackId,
                'distance'      => round($this->coordinatesService->distanceIngameXY($from, $centroid), 1),
                'unverified'    => true,
            ];
        }

        return $neighbours;
    }

    /**
     * Puts the walk graph's verdict over the plain distances: unreachable packs go, reachable ones take the walk
     * around the blocking enemies. Packs the walk graph does not connect keep their plain distance.
     *
     * @param  list<array{enemy_pack_id: int, distance: float, unverified: bool}>                           $plainNeighbours
     * @param  array<int, array{distance: float, detour_ratio: float|null}|null>|null                       $reachability    enemy pack id => the walk, or null when not reachable; null when the walk graph knows nothing
     * @return list<array{enemy_pack_id: int, distance: float, unverified: bool, detour_ratio: float|null}>
     */
    private function applyReachability(array $plainNeighbours, ?array $reachability, int $count): array
    {
        $neighbours = [];
        foreach ($plainNeighbours as $neighbour) {
            $neighbour['detour_ratio'] = null;

            if ($reachability !== null && array_key_exists($neighbour['enemy_pack_id'], $reachability)) {
                $reach = $reachability[$neighbour['enemy_pack_id']];
                if ($reach === null) {
                    continue;
                }

                $neighbour['distance']     = $reach['distance'];
                $neighbour['unverified']   = false;
                $neighbour['detour_ratio'] = $reach['detour_ratio'];
            }

            $neighbours[] = $neighbour;
        }

        usort($neighbours, static fn(array $a, array $b) => $a['distance'] <=> $b['distance']);

        return array_slice($neighbours, 0, $count);
    }

    /**
     * @template TKey of array-key
     *
     * @param  array<TKey, array{position: IngameXY, enemyPackId: int|null}>                       $sources         where each walk starts, and the pack killed there, which blocks nothing
     * @param  array<int, IngameXY>                                                                $targets         enemy pack id => centroid
     * @param  Collection<int, Enemy>                                                              $blockingEnemies
     * @return array<TKey, array<int, array{distance: float, detour_ratio: float|null}|null>>|null source => enemy pack id => the walk, or null when it cannot be reached. Packs the walk graph does not connect, and sources it cannot snap, are left out. Null when the floor has no usable walk graph.
     */
    private function getFloorReachability(Floor $floor, array $sources, array $targets, Collection $blockingEnemies, bool $cache): ?array
    {
        if ($floor->facade || $floor->ui_map_id === null || $sources === [] || $targets === []) {
            return null;
        }

        $walkGraph = $this->walkGraphRepository->findSummaryByUiMapId($floor->ui_map_id);
        if ($walkGraph === null || $walkGraph->run_count < (int)config('keystoneguru.walk_graph.min_runs')) {
            return null;
        }

        $blockerPoints = $this->getBlockerPoints($floor, $blockingEnemies);
        $calculate     = fn(): ?array => $this->calculateFloorReachability($floor, $sources, $targets, $blockerPoints);
        if (!$cache) {
            return $calculate();
        }

        $inputs = [
            array_map(static fn(array $source) => [$source['position']->getX(), $source['position']->getY(), $source['enemyPackId']], $sources),
            array_map(static fn(IngameXY $target) => [$target->getX(), $target->getY()], $targets),
            $blockerPoints,
            config('keystoneguru.walk_graph.aggro'),
            config('keystoneguru.walk_graph.snap_radius'),
            config('keystoneguru.walk_graph.max_walk_distance'),
        ];

        return $this->cacheService->remember(
            sprintf(
                'pack_reachability:%d:%d:%d:%s',
                $floor->id,
                $walkGraph->id,
                $walkGraph->built_at->getTimestamp(),
                md5(json_encode($inputs, JSON_THROW_ON_ERROR)),
            ),
            $calculate,
            '1 day',
        );
    }

    /**
     * @template TKey of array-key
     *
     * @param  array<TKey, array{position: IngameXY, enemyPackId: int|null}>                       $sources
     * @param  array<int, IngameXY>                                                                $targets       enemy pack id => centroid
     * @param  list<array{string, float, float}>                                                   $blockerPoints [blocker key, x, y]
     * @return array<TKey, array<int, array{distance: float, detour_ratio: float|null}|null>>|null
     */
    private function calculateFloorReachability(Floor $floor, array $sources, array $targets, array $blockerPoints): ?array
    {
        $walkGridGraph = $this->walkGraphService->getGridGraphForUiMapId($floor->ui_map_id);
        if ($walkGridGraph === null) {
            return null;
        }

        $aggroRadius     = (float)config('keystoneguru.walk_graph.aggro.radius');
        $maxDetourRatio  = (float)config('keystoneguru.walk_graph.aggro.max_detour_ratio');
        $minDetour       = (float)config('keystoneguru.walk_graph.aggro.min_detour');
        $snapRadius      = (float)config('keystoneguru.walk_graph.snap_radius');
        $maxWalkDistance = (float)config('keystoneguru.walk_graph.max_walk_distance');

        /** @var array<int, array<string, true>> $blockerKeysByNode node => the blockers whose aggro radius covers it */
        $blockerKeysByNode = [];
        foreach ($blockerPoints as [$blockerKey, $x, $y]) {
            foreach ($walkGridGraph->getNodesWithinRadius($x, $y, $aggroRadius) as $node) {
                $blockerKeysByNode[$node][$blockerKey] = true;
            }
        }

        /** @var array<string, list<int>> $nodesByBlockerKey */
        $nodesByBlockerKey = [];
        foreach ($blockerKeysByNode as $node => $blockerKeys) {
            foreach ($blockerKeys as $blockerKey => $true) {
                $nodesByBlockerKey[$blockerKey][] = $node;
            }
        }

        $snappedTargets = [];
        foreach ($targets as $enemyPackId => $centroid) {
            $node = $walkGridGraph->snap($centroid->getX(), $centroid->getY(), $snapRadius);
            if ($node !== null) {
                $snappedTargets[$enemyPackId] = [
                    'node'         => $node,
                    'snapDistance' => self::getNodeDistance($walkGridGraph, $node, $centroid->getX(), $centroid->getY()),
                ];
            }
        }

        $result = [];
        foreach ($sources as $sourceKey => $source) {
            $position = $source['position'];
            $node     = $walkGridGraph->snap($position->getX(), $position->getY(), $snapRadius);
            if ($node === null) {
                continue;
            }

            $sourceSnapDistance = self::getNodeDistance($walkGridGraph, $node, $position->getX(), $position->getY());
            $ownBlockerKey      = $source['enemyPackId'] === null ? null : self::getPackBlockerKey($source['enemyPackId']);

            // The killed pack blocks nothing: its aggro radius only stops a walk where someone else's covers it too
            $terminalNodes = $blockerKeysByNode;
            foreach ($nodesByBlockerKey[$ownBlockerKey] ?? [] as $ownNode) {
                if (count($blockerKeysByNode[$ownNode]) === 1) {
                    unset($terminalNodes[$ownNode]);
                }
            }

            $directDistances = $walkGridGraph->getDistancesFrom($node, $maxWalkDistance);
            $aggroDistances  = $walkGridGraph->getShortestPathsFrom($node, $maxWalkDistance, [], $terminalNodes)['distances'];

            $result[$sourceKey] = [];
            foreach ($snappedTargets as $enemyPackId => $snappedTarget) {
                if ($enemyPackId === $source['enemyPackId'] || !isset($directDistances[$snappedTarget['node']])) {
                    continue;
                }

                $centroid     = $targets[$enemyPackId];
                $blockerKey   = self::getPackBlockerKey($enemyPackId);
                $walkDistance = max(
                    hypot($centroid->getX() - $position->getX(), $centroid->getY() - $position->getY()),
                    $sourceSnapDistance + $directDistances[$snappedTarget['node']] + $snappedTarget['snapDistance'],
                );

                // Both walks end where they first get into the pack's aggro radius, then go straight for its centre
                $directApproach = INF;
                $aggroApproach  = INF;
                foreach ($nodesByBlockerKey[$blockerKey] ?? [$snappedTarget['node']] as $zoneNode) {
                    $toCentroid = self::getNodeDistance($walkGridGraph, $zoneNode, $centroid->getX(), $centroid->getY());
                    if (isset($directDistances[$zoneNode])) {
                        $directApproach = min($directApproach, $directDistances[$zoneNode] + $toCentroid);
                    }

                    if (isset($aggroDistances[$zoneNode])) {
                        $otherBlockerKeys = $blockerKeysByNode[$zoneNode] ?? [];
                        unset($otherBlockerKeys[$blockerKey]);
                        if ($ownBlockerKey !== null) {
                            unset($otherBlockerKeys[$ownBlockerKey]);
                        }

                        if ($otherBlockerKeys === []) {
                            $aggroApproach = min($aggroApproach, $aggroDistances[$zoneNode] + $toCentroid);
                        }
                    }
                }

                if ($aggroApproach === INF) {
                    $result[$sourceKey][$enemyPackId] = null;

                    continue;
                }

                $detour = $directApproach === INF ? 0.0 : max(0.0, $aggroApproach - $directApproach);
                if ($detour <= $minDetour) {
                    $result[$sourceKey][$enemyPackId] = ['distance' => round($walkDistance, 1), 'detour_ratio' => null];

                    continue;
                }

                $detourRatio                      = ($walkDistance + $detour) / max($walkDistance, 1.0);
                $result[$sourceKey][$enemyPackId] = $detourRatio > $maxDetourRatio ? null : [
                    'distance'     => round($walkDistance + $detour, 1),
                    'detour_ratio' => round($detourRatio, 1),
                ];
            }
        }

        return $result;
    }

    /**
     * Where each blocking enemy on the floor may stand: its own position, and for a patrol every point along its
     * patrol path, spaced closely enough that their aggro radii join into one band nobody walks through.
     *
     * @param  Collection<int, Enemy>            $blockingEnemies
     * @return list<array{string, float, float}> [blocker key, x, y]; an enemy in a pack blocks as its pack
     */
    private function getBlockerPoints(Floor $floor, Collection $blockingEnemies): array
    {
        $floorEnemies = $blockingEnemies->where('floor_id', $floor->id)->sortBy('id');

        $patrolIds = $floorEnemies->pluck('enemy_patrol_id')->filter()->unique()->values();
        /** @var Collection<int, EnemyPatrol> $enemyPatrols */
        $enemyPatrols = $patrolIds->isEmpty() ? collect() : EnemyPatrol::query()
            ->with('polyline')
            ->whereIn('id', $patrolIds)
            ->get()
            ->keyBy('id');

        $patrolSpacing = (float)config('keystoneguru.walk_graph.aggro.radius') / 2;

        $points        = [];
        $walkedPatrols = [];
        foreach ($floorEnemies as $enemy) {
            $blockerKey = $enemy->enemy_pack_id === null ?
                sprintf('enemy:%d', $enemy->id) :
                self::getPackBlockerKey($enemy->enemy_pack_id);

            $ingameXY = $this->coordinatesService->calculateIngameLocationForMapLocation(
                new LatLng($enemy->lat, $enemy->lng, $floor),
            );
            $points[] = [$blockerKey, round($ingameXY->getX(), 1), round($ingameXY->getY(), 1)];

            $enemyPatrol = $enemyPatrols->get($enemy->enemy_patrol_id);
            if ($enemyPatrol?->polyline === null || isset($walkedPatrols[$blockerKey][$enemyPatrol->id])) {
                continue;
            }

            $walkedPatrols[$blockerKey][$enemyPatrol->id] = true;

            $previous = null;
            foreach ($enemyPatrol->polyline->getDecodedLatLngs($floor) as $latLng) {
                $vertex = $this->coordinatesService->calculateIngameLocationForMapLocation($latLng);
                if ($previous === null) {
                    $points[] = [$blockerKey, round($vertex->getX(), 1), round($vertex->getY(), 1)];
                } else {
                    $steps = max(1, (int)ceil($this->coordinatesService->distanceIngameXY($previous, $vertex) / $patrolSpacing));
                    for ($step = 1; $step <= $steps; $step++) {
                        $points[] = [
                            $blockerKey,
                            round($previous->getX() + ($vertex->getX() - $previous->getX()) * $step / $steps, 1),
                            round($previous->getY() + ($vertex->getY() - $previous->getY()) * $step / $steps, 1),
                        ];
                    }
                }

                $previous = $vertex;
            }
        }

        return $points;
    }

    private static function getPackBlockerKey(int $enemyPackId): string
    {
        return sprintf('pack:%d', $enemyPackId);
    }

    private static function getNodeDistance(WalkGridGraph $walkGridGraph, int $node, float $x, float $y): float
    {
        [$nodeX, $nodeY] = $walkGridGraph->getNodePosition($node);

        return hypot($nodeX - $x, $nodeY - $y);
    }
}
