<?php

namespace App\Service\CombatLog;

use App\Logic\Structs\IngameXY;
use App\Logic\Structs\LatLng;
use App\Models\CombatLog\CombatLogRouteEnemyResolution;
use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Service\CombatLog\Dtos\EnemyResolutionAnalysis\CombatLogRouteEnemyResolutionAnalysisResult;
use App\Service\CombatLog\Dtos\EnemyResolutionAnalysis\EnemyResolutionGroup;
use App\Service\CombatLog\Dtos\EnemyResolutionAnalysis\EnemyResolutionVerdict;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * @phpstan-type ResolutionPoint array{engaged_x: float, engaged_y: float, mapped_x: float, mapped_y: float, enemy_id: int, npc_id: int|null, route: string|null, created_at: Carbon|null}
 */
readonly class CombatLogRouteEnemyResolutionAnalysisService implements CombatLogRouteEnemyResolutionAnalysisServiceInterface
{
    public function __construct(private CoordinatesServiceInterface $coordinatesService)
    {
    }

    public function analyze(
        Dungeon        $dungeon,
        MappingVersion $mappingVersion,
        ?array         $npcIds = null,
        ?float         $minDistance = null,
    ): CombatLogRouteEnemyResolutionAnalysisResult {
        $minRoutes               = (int)config('keystoneguru.enemy_resolution_analysis.min_routes');
        $minRouteShare           = (float)config('keystoneguru.enemy_resolution_analysis.min_route_share');
        $minDirectionConsistency = (float)config('keystoneguru.enemy_resolution_analysis.min_direction_consistency');
        $minShapeRatio           = (float)config('keystoneguru.enemy_resolution_analysis.min_shape_ratio');

        /** @var Collection<int, Floor> $floors */
        $floors = $dungeon->floors->keyBy('id');

        $resolutions = CombatLogRouteEnemyResolution::query()
            ->where('dungeon_id', $dungeon->id)
            ->where('mapping_version_id', $mappingVersion->id)
            ->when(!empty($npcIds), static fn($builder) => $builder->whereIn('npc_id', $npcIds))
            ->when($minDistance !== null, static fn($builder) => $builder->where('weighted_distance', '>=', $minDistance))
            ->orderBy('id')
            ->get();

        /** @var Collection<int, Enemy> $enemies */
        $enemies = Enemy::query()
            ->whereIn('id', $resolutions->pluck('enemy_id')->unique())
            ->with('enemyPack')
            ->get()
            ->keyBy('id');

        // 1. Every analysable resolution as a pair of ingame points, grouped per mapped pack and floor
        /** @var array<string, array<int, ResolutionPoint>> $pointsPerGroup */
        $pointsPerGroup = [];
        $skippedCount   = 0;

        foreach ($resolutions as $resolution) {
            /** @var Floor|null $floor */
            $floor = $floors->get($resolution->floor_id);
            /** @var Enemy|null $enemy */
            $enemy = $enemies->get($resolution->enemy_id);
            // The engagement is recorded on the floor the log was on, the enemy's position on its own floor - across
            // two floors neither the conversion nor the distance means anything
            if ($floor === null || $floor->facade || ($enemy !== null && $enemy->floor_id !== $floor->id)) {
                $skippedCount++;

                continue;
            }

            try {
                $engaged = $this->coordinatesService->calculateIngameLocationForMapLocation(new LatLng($resolution->lat, $resolution->lng, $floor));
                $mapped  = $this->coordinatesService->calculateIngameLocationForMapLocation(new LatLng($resolution->enemy_lat, $resolution->enemy_lng, $floor));
            } catch (InvalidArgumentException) {
                $skippedCount++;

                continue;
            }

            $route    = $this->getRouteKey($resolution);
            $groupKey = $enemy?->enemy_pack_id === null
                ? sprintf('%d|enemy|%d', $floor->id, $resolution->enemy_id)
                : sprintf('%d|pack|%d', $floor->id, $enemy->enemy_pack_id);

            $pointsPerGroup[$groupKey][] = [
                'engaged_x'  => $engaged->getX(),
                'engaged_y'  => $engaged->getY(),
                'mapped_x'   => $mapped->getX(),
                'mapped_y'   => $mapped->getY(),
                'enemy_id'   => $resolution->enemy_id,
                'npc_id'     => $resolution->npc_id,
                'route'      => $route,
                'created_at' => $resolution->created_at,
            ];
        }

        $totalRouteCount = $this->countRoutes($dungeon, $mappingVersion);

        /** @var Collection<int, Npc> $npcs */
        $npcs = Npc::query()->whereIn('id', $resolutions->pluck('npc_id')->filter()->unique())->get()->keyBy('id');

        // 2. Judge every group
        $groups = [];
        foreach ($pointsPerGroup as $groupKey => $points) {
            $floorId = (int)explode('|', $groupKey)[0];
            /** @var Enemy|null $firstEnemy */
            $firstEnemy = $enemies->get($points[0]['enemy_id']);

            $groups[] = $this->buildGroup(
                $floors->get($floorId),
                str_contains($groupKey, '|pack|') ? $firstEnemy : null,
                $points,
                $npcs,
                $totalRouteCount,
                $minRoutes,
                $minRouteShare,
                $minDirectionConsistency,
                $minShapeRatio,
            );
        }

        usort($groups, static fn(EnemyResolutionGroup $a, EnemyResolutionGroup $b): int => [$a->lowVolume, $a->verdict->severity(), -$a->routeCount] <=> [$b->lowVolume, $b->verdict->severity(), -$b->routeCount]);

        return new CombatLogRouteEnemyResolutionAnalysisResult(
            $this->coordinatesService,
            $mappingVersion,
            $groups,
            $totalRouteCount,
            $minRoutes,
            $minRouteShare,
            $skippedCount,
        );
    }

    /**
     * The routes that recorded any long resolution in the mapping version, regardless of the npc and distance filters -
     * a group's share of routes must not change with what else is filtered out.
     */
    private function countRoutes(Dungeon $dungeon, MappingVersion $mappingVersion): int
    {
        $routes = CombatLogRouteEnemyResolution::query()
            ->select(['source', 'dungeon_route_id'])
            ->where('dungeon_id', $dungeon->id)
            ->where('mapping_version_id', $mappingVersion->id)
            ->whereNotNull('dungeon_route_id')
            ->distinct()
            ->toBase();

        return $routes->newQuery()->fromSub($routes, 'routes')->count();
    }

    /**
     * An imported row's route id belongs to the deployment it came from, so the id alone does not identify a route.
     */
    private function getRouteKey(CombatLogRouteEnemyResolution $resolution): ?string
    {
        return $resolution->dungeon_route_id === null ? null : sprintf('%s|%d', $resolution->source ?? '', $resolution->dungeon_route_id);
    }

    /**
     * @param Enemy|null                  $packEnemy Any enemy of the pack, null when the group is a single enemy without a pack
     * @param array<int, ResolutionPoint> $points
     * @param Collection<int, Npc>        $npcs
     */
    private function buildGroup(
        Floor      $floor,
        ?Enemy     $packEnemy,
        array      $points,
        Collection $npcs,
        int        $totalRouteCount,
        int        $minRoutes,
        float      $minRouteShare,
        float      $minDirectionConsistency,
        float      $minShapeRatio,
    ): EnemyResolutionGroup {
        $count = count($points);

        $engagedX = array_sum(array_column($points, 'engaged_x')) / $count;
        $engagedY = array_sum(array_column($points, 'engaged_y')) / $count;
        $mappedX  = array_sum(array_column($points, 'mapped_x')) / $count;
        $mappedY  = array_sum(array_column($points, 'mapped_y')) / $count;

        // How much the individual offsets agree on a direction, regardless of how long they are
        $unitX       = 0.0;
        $unitY       = 0.0;
        $totalLength = 0.0;
        foreach ($points as $point) {
            $offsetX = $point['engaged_x'] - $point['mapped_x'];
            $offsetY = $point['engaged_y'] - $point['mapped_y'];
            $length  = sqrt($offsetX ** 2 + $offsetY ** 2);
            $totalLength += $length;
            if ($length > 0) {
                $unitX += $offsetX / $length;
                $unitY += $offsetY / $length;
            }
        }
        $directionConsistency = sqrt(($unitX / $count) ** 2 + ($unitY / $count) ** 2);

        $shapeRatio = $this->calculateShapeRatio($points);

        $routes     = array_unique(array_filter(array_column($points, 'route'), static fn(?string $route) => $route !== null));
        $routeCount = count($routes);
        $routeShare = $totalRouteCount > 0 ? $routeCount / $totalRouteCount : 0.0;
        // Rows without a route id (legacy) can't tell us how many routes were affected - judge those on count alone
        $lowVolume = $routeCount > 0 ? ($routeCount < $minRoutes || $routeShare < $minRouteShare) : $count < $minRoutes;

        $verdict = match (true) {
            $directionConsistency < $minDirectionConsistency     => EnemyResolutionVerdict::Scatter,
            $shapeRatio !== null && $shapeRatio < $minShapeRatio => EnemyResolutionVerdict::Converged,
            default                                              => EnemyResolutionVerdict::Displaced,
        };

        $displacement = $this->coordinatesService->distanceBetweenPoints($mappedX, $engagedX, $mappedY, $engagedY);

        $npcCounts = array_count_values(array_filter(array_column($points, 'npc_id'), static fn(?int $npcId) => $npcId !== null));
        arsort($npcCounts);
        $npcNames = implode(', ', array_map(
            static fn(int $npcId): string => __($npcs->get($npcId)->name ?? sprintf('Unknown npc %d', $npcId), [], 'en_US'),
            array_keys($npcCounts),
        ));

        $createdAts = array_filter(array_column($points, 'created_at'));
        $enemyIds   = array_values(array_unique(array_column($points, 'enemy_id')));
        sort($enemyIds);

        $subject = $packEnemy?->enemyPack === null
            ? __('services.combatlog.enemy_resolution_analysis.subject.enemy', ['enemy_id' => $enemyIds[0]])
            : __('services.combatlog.enemy_resolution_analysis.subject.pack', ['group' => $packEnemy->enemyPack->group ?? '-', 'pack_id' => $packEnemy->enemy_pack_id]);

        return new EnemyResolutionGroup(
            floorId: $floor->id,
            enemyPackId: $packEnemy?->enemy_pack_id,
            enemyPackGroup: $packEnemy?->enemyPack?->group,
            enemyIds: $enemyIds,
            npcNames: $npcNames,
            count: $count,
            routeCount: $routeCount,
            routeShare: $routeShare,
            engagedCentroid: $this->coordinatesService->calculateMapLocationForIngameLocation(new IngameXY($engagedX, $engagedY, $floor)),
            mappedCentroid: $this->coordinatesService->calculateMapLocationForIngameLocation(new IngameXY($mappedX, $mappedY, $floor)),
            displacement: $displacement,
            directionConsistency: $directionConsistency,
            shapeRatio: $shapeRatio,
            firstSeen: empty($createdAts) ? null : min($createdAts),
            lastSeen: empty($createdAts) ? null : max($createdAts),
            verdict: $verdict,
            lowVolume: $lowVolume,
            suggestion: __(sprintf(
                'services.combatlog.enemy_resolution_analysis.suggestion.%s',
                $verdict === EnemyResolutionVerdict::Displaced && $shapeRatio === null ? 'displaced_shape_unknown' : $verdict->value,
            ), [
                'subject' => $subject,
                // Offsets in every direction cancel out in the centroids, so scatter reports how far off they each were
                'distance'    => round($verdict === EnemyResolutionVerdict::Scatter ? $totalLength / $count : $displacement),
                'routes'      => $routeCount,
                'share'       => round($routeShare * 100),
                'ratio'       => $shapeRatio === null ? '-' : round($shapeRatio, 2),
                'consistency' => round($directionConsistency, 2),
            ]),
        );
    }

    /**
     * How far apart the members' engaged positions are compared to how far apart they are mapped: about 1 when the pack
     * kept its shape, well below 1 when it bunched up. Median distance to the centroid, so one stray member does not
     * decide it. Null when fewer than 3 members were resolved to, or they are all mapped on one spot.
     *
     * @param array<int, ResolutionPoint> $points
     */
    private function calculateShapeRatio(array $points): ?float
    {
        /** @var array<int, array{engaged_x: float[], engaged_y: float[], mapped_x: float, mapped_y: float}> $members */
        $members = [];
        foreach ($points as $point) {
            $members[$point['enemy_id']]['engaged_x'][] = $point['engaged_x'];
            $members[$point['enemy_id']]['engaged_y'][] = $point['engaged_y'];
            $members[$point['enemy_id']]['mapped_x']    = $point['mapped_x'];
            $members[$point['enemy_id']]['mapped_y']    = $point['mapped_y'];
        }

        if (count($members) < 3) {
            return null;
        }

        $engaged = [];
        $mapped  = [];
        foreach ($members as $member) {
            $engaged[] = [array_sum($member['engaged_x']) / count($member['engaged_x']), array_sum($member['engaged_y']) / count($member['engaged_y'])];
            $mapped[]  = [$member['mapped_x'], $member['mapped_y']];
        }

        $mappedSpread = $this->medianDistanceToCentroid($mapped);
        if ($mappedSpread <= 0.0) {
            return null;
        }

        return $this->medianDistanceToCentroid($engaged) / $mappedSpread;
    }

    /**
     * @param array<int, array{0: float, 1: float}> $points
     */
    private function medianDistanceToCentroid(array $points): float
    {
        $centroidX = array_sum(array_column($points, 0)) / count($points);
        $centroidY = array_sum(array_column($points, 1)) / count($points);

        $distances = array_map(
            fn(array $point): float => $this->coordinatesService->distanceBetweenPoints($centroidX, $point[0], $centroidY, $point[1]),
            $points,
        );
        sort($distances);

        $middle = intdiv(count($distances), 2);

        return count($distances) % 2 === 1 ? $distances[$middle] : ($distances[$middle - 1] + $distances[$middle]) / 2;
    }
}
