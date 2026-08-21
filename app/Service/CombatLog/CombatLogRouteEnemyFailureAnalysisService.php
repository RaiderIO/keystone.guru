<?php

namespace App\Service\CombatLog;

use App\Logic\Structs\IngameXY;
use App\Logic\Structs\LatLng;
use App\Models\CombatLog\CombatLogRouteEnemyFailure;
use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Service\CombatLog\Dtos\EnemyFailureAnalysis\CombatLogRouteEnemyFailureAnalysisResult;
use App\Service\CombatLog\Dtos\EnemyFailureAnalysis\EnemyFailureCluster;
use App\Service\CombatLog\Dtos\EnemyFailureAnalysis\EnemyFailureVerdict;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * @phpstan-type FailurePoint array{x: float, y: float, lat: float, lng: float, route_id: int|null, created_at: Carbon|null}
 * @phpstan-type EnemyPoint   array{enemy: Enemy, x: float, y: float}
 */
readonly class CombatLogRouteEnemyFailureAnalysisService implements CombatLogRouteEnemyFailureAnalysisServiceInterface
{
    public function __construct(
        private CoordinatesServiceInterface                $coordinatesService,
        private CombatLogRouteEnemyFailureServiceInterface $combatLogRouteEnemyFailureService,
    ) {
    }

    public function analyze(
        Dungeon        $dungeon,
        MappingVersion $mappingVersion,
        ?array         $npcIds = null,
        ?int           $minCount = null,
    ): CombatLogRouteEnemyFailureAnalysisResult {
        $clusterRadius = (int)config('keystoneguru.enemy_failure_analysis.cluster_radius_yd');
        $minCount ??= (int)config('keystoneguru.enemy_failure_analysis.min_count');
        $minRoutes = (int)config('keystoneguru.enemy_failure_analysis.min_routes');

        /** @var Collection<int, Floor> $floors */
        $floors = $dungeon->floors->keyBy('id');

        // 1. Collect every analysable failure as an ingame point, grouped per (npc, floor)
        /** @var array<string, array<int, FailurePoint>> $pointsPerGroup "npc_id|floor_id" => points */
        $pointsPerGroup = [];
        $skippedCount   = 0;

        foreach ($this->failuresQuery($dungeon, $mappingVersion, $npcIds)->cursor() as $failure) {
            /** @var CombatLogRouteEnemyFailure $failure */
            /** @var Floor|null $floor */
            $floor = $floors->get($failure->floor_id);
            if ($failure->npc_id === null || $floor === null || $floor->facade) {
                $skippedCount++;

                continue;
            }

            try {
                $ingameXY = $this->coordinatesService->calculateIngameLocationForMapLocation(new LatLng($failure->lat, $failure->lng, $floor));
            } catch (InvalidArgumentException) {
                // A floor without ingame coordinates (#3904) - nothing to measure with
                $skippedCount++;

                continue;
            }

            $pointsPerGroup[sprintf('%d|%d', $failure->npc_id, $failure->floor_id)][] = [
                'x'          => $ingameXY->getX(),
                'y'          => $ingameXY->getY(),
                'lat'        => (float)$failure->lat,
                'lng'        => (float)$failure->lng,
                'route_id'   => $failure->dungeon_route_id,
                'created_at' => $failure->created_at,
            ];
        }

        if (empty($pointsPerGroup)) {
            return new CombatLogRouteEnemyFailureAnalysisResult($this->coordinatesService, $mappingVersion, [], $clusterRadius, $minCount, $minRoutes, $skippedCount);
        }

        // 2. The mapped enemies of every npc that failed, as ingame points too
        $failedNpcIds    = collect(array_keys($pointsPerGroup))->map(static fn(string $key): int => (int)explode('|', $key)[0])->unique()->values();
        $enemiesPerNpcId = $this->getEnemyPointsPerNpcId($mappingVersion, $failedNpcIds, $floors);
        /** @var Collection<int, Npc> $npcs */
        $npcs = Npc::query()->whereIn('id', $failedNpcIds)->get()->keyBy('id');

        // 3. Cluster each group and judge every cluster
        $clusters = [];
        foreach ($pointsPerGroup as $key => $points) {
            [$npcId, $floorId] = array_map(intval(...), explode('|', $key));
            /** @var Floor $floor */
            $floor   = $floors->get($floorId);
            $npcName = __($npcs->get($npcId)->name ?? sprintf('Unknown npc %d', $npcId), [], 'en_US');

            foreach ($this->clusterPoints($points, $clusterRadius) as $memberIndexes) {
                $clusters[] = $this->buildCluster(
                    $npcId,
                    $npcName,
                    $floor,
                    array_map(static fn(int $index) => $points[$index], $memberIndexes),
                    $enemiesPerNpcId[$npcId] ?? [],
                    $minCount,
                    $minRoutes,
                );
            }
        }

        usort($clusters, static function (EnemyFailureCluster $a, EnemyFailureCluster $b): int {
            return [$a->lowVolume, $a->verdict->severity(), -$a->count] <=> [$b->lowVolume, $b->verdict->severity(), -$b->count];
        });

        return new CombatLogRouteEnemyFailureAnalysisResult($this->coordinatesService, $mappingVersion, $clusters, $clusterRadius, $minCount, $minRoutes, $skippedCount);
    }

    /**
     * @param  int[]|null                          $npcIds
     * @return Builder<CombatLogRouteEnemyFailure>
     */
    private function failuresQuery(Dungeon $dungeon, MappingVersion $mappingVersion, ?array $npcIds): Builder
    {
        $query = CombatLogRouteEnemyFailure::query()
            ->where('dungeon_id', $dungeon->id)
            ->where('mapping_version_id', $mappingVersion->id)
            ->whereNotNull('npc_id')
            ->orderBy('id');

        if (!empty($npcIds)) {
            $query->whereIn('npc_id', $npcIds);
        }

        // 0-enemy-forces npcs are noise everywhere the failures are read - see CombatLogRouteEnemyFailureService
        $zeroEnemyForcesNpcIds = $this->combatLogRouteEnemyFailureService->getZeroEnemyForcesNpcIds($mappingVersion);
        if (!empty($zeroEnemyForcesNpcIds)) {
            $query->whereNotIn('npc_id', $zeroEnemyForcesNpcIds);
        }

        return $query;
    }

    /**
     * @param  Collection<int, int>               $npcIds
     * @param  Collection<int, Floor>             $floors
     * @return array<int, array<int, EnemyPoint>> npc_id => enemy points (facade-floor and unconvertible enemies left out)
     */
    private function getEnemyPointsPerNpcId(MappingVersion $mappingVersion, Collection $npcIds, Collection $floors): array
    {
        $result = [];

        /** @var Collection<int, Enemy> $enemies */
        $enemies = $mappingVersion->enemies()
            ->whereIn('npc_id', $npcIds)
            ->whereNotNull('floor_id')
            ->whereNull('teeming')
            ->with(['enemyPack'])
            ->get();

        foreach ($enemies as $enemy) {
            /** @var Floor|null $floor */
            $floor = $floors->get($enemy->floor_id);
            if ($floor === null || $floor->facade) {
                continue;
            }

            try {
                $ingameXY = $this->coordinatesService->calculateIngameLocationForMapLocation(new LatLng($enemy->lat, $enemy->lng, $floor));
            } catch (InvalidArgumentException) {
                continue;
            }

            $result[$enemy->npc_id][] = ['enemy' => $enemy, 'x' => $ingameXY->getX(), 'y' => $ingameXY->getY()];
        }

        return $result;
    }

    /**
     * Leader clustering over a grid hash: a point that is not yet in a cluster starts one, and every unassigned point
     * within the radius of that leader (found in the 3x3 neighbouring cells) joins it. O(n * cell occupancy) rather than
     * O(n^2) - thousands of failures per dungeon are normal.
     *
     * @param  array<int, FailurePoint> $points
     * @return array<int, int[]>        clusters as lists of point indexes
     */
    private function clusterPoints(array $points, int $radius): array
    {
        /** @var array<string, int[]> $cells "cx,cy" => point indexes */
        $cells = [];
        foreach ($points as $index => $point) {
            $cells[sprintf('%d,%d', (int)floor($point['x'] / $radius), (int)floor($point['y'] / $radius))][] = $index;
        }

        $assigned = [];
        $clusters = [];
        foreach ($points as $index => $leader) {
            if (isset($assigned[$index])) {
                continue;
            }

            $members          = [$index];
            $assigned[$index] = true;

            $cellX = (int)floor($leader['x'] / $radius);
            $cellY = (int)floor($leader['y'] / $radius);
            for ($dx = -1; $dx <= 1; $dx++) {
                for ($dy = -1; $dy <= 1; $dy++) {
                    foreach ($cells[sprintf('%d,%d', $cellX + $dx, $cellY + $dy)] ?? [] as $candidateIndex) {
                        if (isset($assigned[$candidateIndex])) {
                            continue;
                        }

                        $candidate = $points[$candidateIndex];
                        if ($this->coordinatesService->distanceBetweenPoints($leader['x'], $candidate['x'], $leader['y'], $candidate['y']) <= $radius) {
                            $members[]                 = $candidateIndex;
                            $assigned[$candidateIndex] = true;
                        }
                    }
                }
            }

            $clusters[] = $members;
        }

        return $clusters;
    }

    /**
     * @param array<int, FailurePoint> $members
     * @param array<int, EnemyPoint>   $enemyPoints The mapped enemies of the cluster's npc, any floor
     */
    private function buildCluster(
        int    $npcId,
        string $npcName,
        Floor  $floor,
        array  $members,
        array  $enemyPoints,
        int    $minCount,
        int    $minRoutes,
    ): EnemyFailureCluster {
        $count = count($members);

        $centroidX = array_sum(array_column($members, 'x')) / $count;
        $centroidY = array_sum(array_column($members, 'y')) / $count;

        $centroidIngameXY = new IngameXY($centroidX, $centroidY, $floor);
        $centroid         = $this->coordinatesService->calculateMapLocationForIngameLocation($centroidIngameXY);

        $routeIds   = array_filter(array_unique(array_column($members, 'route_id')), static fn($routeId) => $routeId !== null);
        $routeCount = count($routeIds);

        $createdAts = array_filter(array_column($members, 'created_at'));
        $firstSeen  = empty($createdAts) ? null : min($createdAts);
        $lastSeen   = empty($createdAts) ? null : max($createdAts);

        // Nearest mapped enemy of this npc on any floor, and how many are in range on this floor
        $engagementRange       = (float)($floor->enemy_engagement_max_range ?? config('keystoneguru.enemy_engagement_max_range_default'));
        $nearest               = null;
        $nearestDistance       = null;
        $enemiesWithinRange    = 0;
        $otherFloorWithinRange = false;
        foreach ($enemyPoints as $enemyPoint) {
            $distance = $this->coordinatesService->distanceBetweenPoints($centroidX, $enemyPoint['x'], $centroidY, $enemyPoint['y']);
            if ($nearestDistance === null || $distance < $nearestDistance) {
                $nearest         = $enemyPoint['enemy'];
                $nearestDistance = $distance;
            }

            if ($distance <= $engagementRange) {
                if ($enemyPoint['enemy']->floor_id === $floor->id) {
                    $enemiesWithinRange++;
                } else {
                    $otherFloorWithinRange = true;
                }
            }
        }

        $verdict = match (true) {
            $nearest === null       => EnemyFailureVerdict::NpcNotMapped,
            $enemiesWithinRange > 0 => EnemyFailureVerdict::EnemiesExhausted,
            $otherFloorWithinRange  => EnemyFailureVerdict::WrongFloorArtifact,
            default                 => EnemyFailureVerdict::NoEnemyInRange,
        };

        $avgFailuresPerRoute = $count / max($routeCount, 1);
        // Rows without a route id (legacy) can't tell us how many routes were affected - judge those on count alone
        $lowVolume = $count < $minCount || ($routeCount > 0 && $routeCount < $minRoutes);

        return new EnemyFailureCluster(
            npcId: $npcId,
            npcName: $npcName,
            floorId: $floor->id,
            count: $count,
            routeCount: $routeCount,
            avgFailuresPerRoute: $avgFailuresPerRoute,
            centroid: $centroid,
            centroidIngameXY: $centroidIngameXY,
            hull: $this->convexHull($members, $floor),
            firstSeen: $firstSeen,
            lastSeen: $lastSeen,
            nearestEnemyId: $nearest?->id,
            nearestEnemyDistance: $nearestDistance,
            nearestEnemyFloorId: $nearest?->floor_id,
            nearestEnemyPackGroup: $nearest?->enemyPack?->group,
            enemiesWithinRange: $enemiesWithinRange,
            verdict: $verdict,
            lowVolume: $lowVolume,
            suggestion: __(sprintf('services.combatlog.enemy_failure_analysis.suggestion.%s', $verdict->value), [
                'npc'      => $npcName,
                'count'    => $count,
                'routes'   => $routeCount,
                'avg'      => round($avgFailuresPerRoute, 1),
                'distance' => $nearestDistance === null ? '-' : round($nearestDistance),
                'range'    => round($engagementRange),
                'enemies'  => $enemiesWithinRange,
                'enemy_id' => $nearest->id ?? '-',
            ]),
        );
    }

    /**
     * Andrew's monotone chain over the members' map locations. Empty for fewer than 3 distinct points (or collinear
     * ones) - the front-end then draws just the centroid marker.
     *
     * @param  array<int, FailurePoint> $members
     * @return LatLng[]
     */
    private function convexHull(array $members, Floor $floor): array
    {
        $points = [];
        foreach ($members as $member) {
            $points[sprintf('%.3f,%.3f', $member['lat'], $member['lng'])] = [$member['lat'], $member['lng']];
        }
        $points = array_values($points);

        if (count($points) < 3) {
            return [];
        }

        sort($points);

        $cross = static fn(array $o, array $a, array $b): float => ($a[0] - $o[0]) * ($b[1] - $o[1]) - ($a[1] - $o[1]) * ($b[0] - $o[0]);

        $lower = [];
        foreach ($points as $point) {
            while (count($lower) >= 2 && $cross($lower[count($lower) - 2], $lower[count($lower) - 1], $point) <= 0) {
                array_pop($lower);
            }
            $lower[] = $point;
        }

        $upper = [];
        foreach (array_reverse($points) as $point) {
            while (count($upper) >= 2 && $cross($upper[count($upper) - 2], $upper[count($upper) - 1], $point) <= 0) {
                array_pop($upper);
            }
            $upper[] = $point;
        }

        array_pop($lower);
        array_pop($upper);
        $hull = array_merge($lower, $upper);

        if (count($hull) < 3) {
            return [];
        }

        return array_map(static fn(array $point): LatLng => new LatLng($point[0], $point[1], $floor), $hull);
    }
}
