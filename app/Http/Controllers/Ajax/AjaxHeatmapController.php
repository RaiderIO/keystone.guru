<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Http\Requests\Heatmap\AjaxGetDataFormRequest;
use App\Service\CombatLogEvent\CombatLogEventServiceInterface;
use App\Service\CombatLogEvent\Models\CombatLogEventFilter;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Http\JsonResponse;
use Teapot\StatusCode;

class AjaxHeatmapController extends Controller
{
//    public function getData(
//        GetDataFormRequest             $request,
//        CombatLogEventServiceInterface $combatLogEventService
//    ): JsonResponse {
//        return \response()->json(
//            $combatLogEventService->getCombatLogEvents(
//                CombatLogEventFilter::fromArray($request->validated())
//            )->toArray(),
//            StatusCode::OK
//        );
//    }

    public function getData(
        AjaxGetDataFormRequest         $request,
        CombatLogEventServiceInterface $combatLogEventService
    ): JsonResponse {
        return \response()->json(
            $combatLogEventService->getGridAggregation(
                CombatLogEventFilter::fromArray($request->validated())
            )->toArray(),
            StatusCode::OK
        );
    }
}
