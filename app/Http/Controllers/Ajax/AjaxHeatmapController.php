<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Http\Requests\Heatmap\AjaxGetDataFormRequest;
use App\Service\RaiderIO\Dtos\HeatmapDataFilter;
use App\Service\RaiderIO\RaiderIOApiServiceInterface;
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
        AjaxGetDataFormRequest      $request,
        RaiderIOApiServiceInterface $raiderIOApiService
    ): JsonResponse {
        return \response()->json(
            $raiderIOApiService->getHeatmapData(
                HeatmapDataFilter::fromArray($request->validated())
            )->toArray(),
            StatusCode::OK
        );
    }
}
