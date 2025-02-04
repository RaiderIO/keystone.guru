<?php

namespace App\Service\RaiderIO\Dtos;

use App\Models\Laratrust\Role;
use App\Service\CombatLogEvent\Dtos\CombatLogEventFilter;
use App\Service\CombatLogEvent\Dtos\CombatLogEventGridAggregationResult;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Support\Facades\Auth;

class RaiderIOHeatmapGridResponse extends CombatLogEventGridAggregationResult
{
    public function __construct(
        CoordinatesServiceInterface $coordinatesService,
        CombatLogEventFilter        $combatLogEventFilter,
        array                       $results,
        int                         $runCount,
        private readonly string     $url,
        bool                        $floorsAsArray = false,
    ) {
        parent::__construct($coordinatesService, $combatLogEventFilter, $results, $runCount, $floorsAsArray);
    }

    public function toArray(): array
    {
        $result = parent::toArray();

        if (Auth::check() && (Auth::user()->hasRole(Role::roles([Role::ROLE_ADMIN, Role::ROLE_INTERNAL_TEAM])))) {
            $result['url'] = $this->url;
        }

        return $result;
    }
}
