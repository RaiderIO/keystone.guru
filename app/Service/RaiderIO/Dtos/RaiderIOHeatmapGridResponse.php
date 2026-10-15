<?php

namespace App\Service\RaiderIO\Dtos;

use App\Models\Laratrust\Role;
use App\Models\User;
use App\Service\CombatLogEvent\Dtos\CombatLogEventFilter;
use App\Service\CombatLogEvent\Dtos\CombatLogEventGridAggregationResult;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Override;

/**
 * This represents the Raider.io version of the response from Opensearch. It uses much of the same
 * structure, but has a few differences in the way it is presented.
 */
class RaiderIOHeatmapGridResponse extends CombatLogEventGridAggregationResult
{
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

        return $result;
    }
}
