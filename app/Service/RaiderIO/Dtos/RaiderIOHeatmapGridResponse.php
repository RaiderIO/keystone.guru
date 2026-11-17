<?php

namespace App\Service\RaiderIO\Dtos;

use App\Models\Laratrust\Role;
use App\Models\User;
use App\Service\CombatLogEvent\Dtos\CombatLogEventFilter;
use App\Service\CombatLogEvent\Dtos\CombatLogEventGridAggregationResult;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\RaiderIO\HeatmapHotspotServiceInterface;
use Override;

/**
 * This represents the Raider.io version of the response from Opensearch. It uses much of the same
 * structure, but has a few differences in the way it is presented.
 */
class RaiderIOHeatmapGridResponse extends CombatLogEventGridAggregationResult
{
    private ?HeatmapHotspotServiceInterface $heatmapHotspotService = null;

    /** @var array<int|string, mixed>|null */
    private ?array $hotspotsByFloor = null;

    /**
     * @param array<int, array<int|string, int>> $results
     */
    public function __construct(
        CoordinatesServiceInterface $coordinatesService,
        CombatLogEventFilter        $combatLogEventFilter,
        array                       $results,
        int                         $runCount,
        private readonly int        $maxSamplesInGrid,
        private readonly string     $url,
        bool                        $floorsAsArray = false,
        private readonly ?User      $user = null,
    ) {
        parent::__construct($coordinatesService, $combatLogEventFilter, $results, $runCount, $floorsAsArray);
    }

    #[Override]
    public function toArray(): array
    {
        $result = parent::toArray();

        if ($this->user?->hasRole(Role::roles(Role::ROLES_INTERNAL))) {
            $result['url'] = $this->url;
        }
        // Override the weight_max
        $result['weight_max'] = $this->maxSamplesInGrid;

        if ($this->heatmapHotspotService !== null && $this->hotspotsByFloor !== null) {
            $result['hotspots'] = $this->heatmapHotspotService->convertHotspotsByFloor(
                $this->getCombatLogEventFilter()->getDungeon(),
                $this->getCurrentMappingVersion(),
                $this->hotspotsByFloor,
                $this->isUseFacade(),
                $this->isUseFacadeNavigation(),
            );
        }

        return $result;
    }

    /**
     * @param array<int|string, mixed>|null $hotspotsByFloor The response's hotspotsByFloor; null when it had none
     */
    public function setHotspots(HeatmapHotspotServiceInterface $heatmapHotspotService, ?array $hotspotsByFloor): self
    {
        $this->heatmapHotspotService = $heatmapHotspotService;
        $this->hotspotsByFloor       = $hotspotsByFloor;

        return $this;
    }
}
