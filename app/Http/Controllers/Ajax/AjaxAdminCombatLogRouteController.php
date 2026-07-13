<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ajax\AjaxAdminCombatLogRouteDeleteEnemyFailuresFormRequest;
use App\Http\Requests\Ajax\AjaxAdminCombatLogRouteGetEnemyFailuresFormRequest;
use App\Models\CombatLog\CombatLogRouteEnemyFailure;
use App\Service\CombatLog\CombatLogRouteEnemyFailureServiceInterface;
use Illuminate\Http\JsonResponse;
use Teapot\StatusCode;

class AjaxAdminCombatLogRouteController extends Controller
{
    public function getEnemyFailures(
        AjaxAdminCombatLogRouteGetEnemyFailuresFormRequest $request,
        CombatLogRouteEnemyFailureServiceInterface         $combatLogRouteEnemyFailureService,
    ): JsonResponse {
        return response()->json(
            $combatLogRouteEnemyFailureService
                ->getEnemyFailureHeatmapData($request->dungeon(), $request->validated('npc_id'))
                ->toArray(),
            StatusCode::OK,
        );
    }

    public function deleteEnemyFailures(AjaxAdminCombatLogRouteDeleteEnemyFailuresFormRequest $request): JsonResponse
    {
        CombatLogRouteEnemyFailure::query()
            ->where('dungeon_id', $request->dungeon()->id)
            ->delete();

        return response()->json([], StatusCode::OK);
    }
}
