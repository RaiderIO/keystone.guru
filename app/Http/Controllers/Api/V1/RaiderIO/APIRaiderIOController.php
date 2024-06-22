<?php

namespace App\Http\Controllers\Api\V1\RaiderIO;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RaiderIO\GetHeatmapDataFormRequest;
use App\Http\Resources\RaiderIO\CombatLogEventGridAggregationResultResource;
use App\Service\CombatLogEvent\CombatLogEventServiceInterface;
use App\Service\CombatLogEvent\Models\CombatLogEventFilter;

class APIRaiderIOController extends Controller
{
    public function getHeatmapData(
        GetHeatmapDataFormRequest      $request,
        CombatLogEventServiceInterface $combatLogEventService): CombatLogEventGridAggregationResultResource
    {
        return new CombatLogEventGridAggregationResultResource(
            $combatLogEventService->getGridAggregation(
                CombatLogEventFilter::fromArray($request->validated())
            )
        );
    }
}
