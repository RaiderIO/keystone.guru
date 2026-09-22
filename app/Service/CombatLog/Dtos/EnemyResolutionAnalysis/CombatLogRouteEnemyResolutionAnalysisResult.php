<?php

namespace App\Service\CombatLog\Dtos\EnemyResolutionAnalysis;

use App\Logic\Structs\LatLng;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 */
class CombatLogRouteEnemyResolutionAnalysisResult implements Arrayable
{
    private bool $useFacade;

    /**
     * @param EnemyResolutionGroup[] $groups       Sorted most actionable first
     * @param int                    $routeCount   Distinct routes that recorded any long resolution in the analysed set
     * @param int                    $skippedCount Resolutions that could not be analysed (a floor without ingame coordinates)
     */
    public function __construct(
        private readonly CoordinatesServiceInterface $coordinatesService,
        private readonly MappingVersion              $mappingVersion,
        public readonly array                        $groups,
        public readonly int                          $routeCount,
        public readonly int                          $minRoutes,
        public readonly float                        $minRouteShare,
        public readonly int                          $skippedCount,
    ) {
        $this->useFacade = User::shouldUseFacadeMapStyle($mappingVersion);
    }

    public function toArray(): array
    {
        /** @var Floor|null $facadeFloor */
        $facadeFloor = $this->useFacade ? $this->mappingVersion->dungeon->floors->where('facade', true)->first() : null;

        return [
            'data' => array_map(function (EnemyResolutionGroup $group) use ($facadeFloor): array {
                $engaged = $this->convert($group->engagedCentroid);
                $mapped  = $this->convert($group->mappedCentroid);

                return [
                    'floor_id'              => $facadeFloor->id ?? $group->floorId,
                    'enemy_pack_id'         => $group->enemyPackId,
                    'enemy_pack_group'      => $group->enemyPackGroup,
                    'enemy_ids'             => $group->enemyIds,
                    'npc_names'             => $group->npcNames,
                    'count'                 => $group->count,
                    'route_count'           => $group->routeCount,
                    'route_share'           => round($group->routeShare, 3),
                    'engaged_centroid'      => ['lat' => $engaged->getLat(2), 'lng' => $engaged->getLng(2)],
                    'mapped_centroid'       => ['lat' => $mapped->getLat(2), 'lng' => $mapped->getLng(2)],
                    'displacement'          => round($group->displacement, 1),
                    'direction_consistency' => round($group->directionConsistency, 2),
                    'shape_ratio'           => $group->shapeRatio === null ? null : round($group->shapeRatio, 2),
                    'first_seen'            => $group->firstSeen?->toIso8601String(),
                    'last_seen'             => $group->lastSeen?->toIso8601String(),
                    'verdict'               => $group->verdict->value,
                    'low_volume'            => $group->lowVolume,
                    'suggestion'            => $group->suggestion,
                ];
            }, $this->groups),
            'verdicts' => collect(EnemyResolutionVerdict::cases())
                ->mapWithKeys(static fn(EnemyResolutionVerdict $verdict) => [$verdict->value => ['label' => $verdict->label(), 'color' => $verdict->color()]])
                ->all(),
            'route_count'     => $this->routeCount,
            'min_routes'      => $this->minRoutes,
            'min_route_share' => $this->minRouteShare,
            'skipped_count'   => $this->skippedCount,
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
