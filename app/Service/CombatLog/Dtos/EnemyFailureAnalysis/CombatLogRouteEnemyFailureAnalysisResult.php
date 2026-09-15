<?php

namespace App\Service\CombatLog\Dtos\EnemyFailureAnalysis;

use App\Logic\Structs\LatLng;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 */
class CombatLogRouteEnemyFailureAnalysisResult implements Arrayable
{
    private bool $useFacade;

    /**
     * @param EnemyFailureCluster[] $clusters     Sorted most urgent first
     * @param int                   $skippedCount Failures that could not be analysed (no npc, or a floor without ingame coordinates)
     */
    public function __construct(
        private readonly CoordinatesServiceInterface $coordinatesService,
        private readonly MappingVersion              $mappingVersion,
        public readonly array                        $clusters,
        public readonly int                          $clusterRadiusYd,
        public readonly int                          $minCount,
        public readonly int                          $minRoutes,
        public readonly int                          $skippedCount,
    ) {
        $this->useFacade = User::getCurrentUserMapFacadeStyle() === User::MAP_FACADE_STYLE_FACADE;
    }

    public function toArray(): array
    {
        /** @var Floor|null $facadeFloor */
        $facadeFloor = $this->useFacade ? $this->mappingVersion->dungeon->floors->where('facade', true)->first() : null;

        return [
            'data' => array_map(function (EnemyFailureCluster $cluster) use ($facadeFloor): array {
                $centroid = $this->convert($cluster->centroid);
                $hull     = array_map(fn(LatLng $latLng): array => [$this->convert($latLng)->getLat(2), $this->convert($latLng)->getLng(2)], $cluster->hull);

                return [
                    'npc_id'                   => $cluster->npcId,
                    'npc_name'                 => $cluster->npcName,
                    'floor_id'                 => $facadeFloor->id ?? $cluster->floorId,
                    'count'                    => $cluster->count,
                    'route_count'              => $cluster->routeCount,
                    'avg_failures_per_route'   => round($cluster->avgFailuresPerRoute, 2),
                    'centroid'                 => ['lat' => $centroid->getLat(2), 'lng' => $centroid->getLng(2), 'floor_id' => $facadeFloor->id ?? $cluster->floorId],
                    'centroid_ingame'          => ['x' => $cluster->centroidIngameXY->getX(1), 'y' => $cluster->centroidIngameXY->getY(1)],
                    'hull'                     => $hull,
                    'first_seen'               => $cluster->firstSeen?->toIso8601String(),
                    'last_seen'                => $cluster->lastSeen?->toIso8601String(),
                    'nearest_enemy_id'         => $cluster->nearestEnemyId,
                    'nearest_enemy_distance'   => $cluster->nearestEnemyDistance === null ? null : round($cluster->nearestEnemyDistance, 1),
                    'nearest_enemy_floor_id'   => $cluster->nearestEnemyFloorId,
                    'nearest_enemy_pack_group' => $cluster->nearestEnemyPackGroup,
                    'enemies_within_range'     => $cluster->enemiesWithinRange,
                    'verdict'                  => $cluster->verdict->value,
                    'low_volume'               => $cluster->lowVolume,
                    'suggestion'               => $cluster->suggestion,
                ];
            }, $this->clusters),
            'verdicts' => collect(EnemyFailureVerdict::cases())
                ->mapWithKeys(static fn(EnemyFailureVerdict $verdict) => [$verdict->value => ['label' => $verdict->label(), 'color' => $verdict->color()]])
                ->all(),
            'cluster_radius_yd' => $this->clusterRadiusYd,
            'min_count'         => $this->minCount,
            'min_routes'        => $this->minRoutes,
            'skipped_count'     => $this->skippedCount,
        ];
    }

    /**
     * Only for unit tests and the CLI.
     */
    public function setUseFacade(bool $useFacade): self
    {
        $this->useFacade = $useFacade;

        return $this;
    }

    private function convert(LatLng $latLng): LatLng
    {
        if (!$this->useFacade || $latLng->getFloor() === null) {
            return $latLng;
        }

        return $this->coordinatesService->convertMapLocationToFacadeMapLocation($this->mappingVersion, $latLng);
    }
}
