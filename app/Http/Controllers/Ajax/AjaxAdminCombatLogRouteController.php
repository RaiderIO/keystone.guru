<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ajax\AjaxAdminCombatLogRouteDeleteEnemyFailuresFormRequest;
use App\Http\Requests\Ajax\AjaxAdminCombatLogRouteDeleteEnemyResolutionsFormRequest;
use App\Http\Requests\Ajax\AjaxAdminCombatLogRouteGetEnemyFailuresFormRequest;
use App\Http\Requests\Ajax\AjaxAdminCombatLogRouteGetEnemyResolutionLinesFormRequest;
use App\Http\Requests\Ajax\AjaxAdminCombatLogRouteGetEnemyResolutionsFormRequest;
use App\Models\CombatLog\CombatLogRouteEnemyFailure;
use App\Models\CombatLog\CombatLogRouteEnemyResolution;
use App\Service\CombatLog\CombatLogRouteEnemyFailureAnalysisServiceInterface;
use App\Service\CombatLog\CombatLogRouteEnemyFailureServiceInterface;
use App\Service\CombatLog\CombatLogRouteEnemyResolutionServiceInterface;
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
                ->getEnemyFailureHeatmapData($request->dungeon(), $request->mappingVersion(), $request->validated('npc_id'))
                ->toArray(),
            StatusCode::OK,
        );
    }

    public function getEnemyFailureClusters(
        AjaxAdminCombatLogRouteGetEnemyFailuresFormRequest $request,
        CombatLogRouteEnemyFailureAnalysisServiceInterface $combatLogRouteEnemyFailureAnalysisService,
    ): JsonResponse {
        return response()->json(
            $combatLogRouteEnemyFailureAnalysisService
                ->analyze($request->dungeon(), $request->mappingVersion(), $request->validated('npc_id'))
                ->toArray(),
            StatusCode::OK,
        );
    }

    public function getEnemyResolutions(
        AjaxAdminCombatLogRouteGetEnemyResolutionsFormRequest $request,
        CombatLogRouteEnemyResolutionServiceInterface         $combatLogRouteEnemyResolutionService,
    ): JsonResponse {
        return response()->json(
            $combatLogRouteEnemyResolutionService
                ->getResolutionHeatmapData(
                    $request->dungeon(),
                    $request->mappingVersion(),
                    $request->validated('npc_id'),
                    $request->metric(),
                    $request->minDistance(),
                )
                ->toArray(),
            StatusCode::OK,
        );
    }

    public function getEnemyResolutionLines(
        AjaxAdminCombatLogRouteGetEnemyResolutionLinesFormRequest $request,
        CombatLogRouteEnemyResolutionServiceInterface             $combatLogRouteEnemyResolutionService,
    ): JsonResponse {
        return response()->json([
            'data' => $combatLogRouteEnemyResolutionService->getResolutionLines(
                $request->dungeon(),
                $request->mappingVersion(),
                $request->validated('npc_id'),
                $request->minDistance(),
                $request->limit(),
            ),
        ], StatusCode::OK);
    }

    public function deleteEnemyResolutions(AjaxAdminCombatLogRouteDeleteEnemyResolutionsFormRequest $request): JsonResponse
    {
        CombatLogRouteEnemyResolution::query()
            ->where('dungeon_id', $request->dungeon()->id)
            ->delete();

        return response()->json([], StatusCode::OK);
    }

    public function deleteEnemyFailures(AjaxAdminCombatLogRouteDeleteEnemyFailuresFormRequest $request): JsonResponse
    {
        CombatLogRouteEnemyFailure::query()
            ->where('dungeon_id', $request->dungeon()->id)
            ->delete();

        return response()->json([], StatusCode::OK);
    }
}
