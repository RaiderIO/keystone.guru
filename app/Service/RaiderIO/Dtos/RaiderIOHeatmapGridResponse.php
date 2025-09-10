<?php

namespace App\Service\RaiderIO\Dtos;

use App\Models\Laratrust\Role;
use App\Service\CombatLogEvent\Dtos\CombatLogEventFilter;
use App\Service\CombatLogEvent\Dtos\CombatLogEventGridAggregationResult;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Support\Facades\Auth;

/**
 * This represents the Raider.io version of the response from Opensearch. It uses much of the same
 * structure, but has a few differences in the way it is presented.
 */
class RaiderIOHeatmapGridResponse extends CombatLogEventGridAggregationResult
{
    public function __construct(
        CoordinatesServiceInterface $coordinatesService,
        CombatLogEventFilter        $combatLogEventFilter,
        array                       $results,
        int                         $runCount,
        private readonly int        $maxSamplesInGrid,
        private readonly string     $url,
        bool                        $floorsAsArray = false,
    ) {
        parent::__construct($coordinatesService, $combatLogEventFilter, $results, $runCount, $floorsAsArray);
    }

    public function toArray(): array
    {
        $result = parent::toArray();

        if (Auth::check() && (Auth::user()->hasRole(Role::roles([
            Role::ROLE_ADMIN,
            Role::ROLE_INTERNAL_TEAM,
        ])))) {
            $result['url'] = $this->url;
        }
        // Override the weight_max
        $result['weight_max'] = $this->maxSamplesInGrid;

        return $result;
    }
}
