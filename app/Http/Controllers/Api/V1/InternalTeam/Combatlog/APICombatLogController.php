<?php

namespace App\Http\Controllers\Api\V1\InternalTeam\Combatlog;

use App\Http\Controllers\Controller;
use App\Http\Models\Request\CombatLog\Route\CombatLogRouteRequestModel;
use App\Http\Requests\Api\V1\CombatLog\Route\CombatLogRouteRequest;
use App\Http\Resources\CombatLog\Route\CombatLogRouteCorrectionRequestResource;
use App\Http\Resources\DungeonRoute\DungeonRouteResource;
use App\Http\Resources\DungeonRoute\DungeonRouteSummaryResource;
use App\Service\CombatLog\CombatLogRouteDungeonRouteServiceInterface;

class APICombatLogController extends Controller
{
    /**
     * @OA\Post(
     *     operationId="createCombatLogRoute",
     *     path="/api/v1/combatlog/route",
     *     summary="Create a new route from a combat log",
     *     tags={"CombatLog"},
     *
     *     @OA\RequestBody(
     *           description="Request object containing all parameters required to generate a route from a combat log",
     *          required=true,
     *
     *          @OA\JsonContent(ref="#/components/schemas/CombatLogRouteRequest")
     *      ),
     *
     *     @OA\Response(response=200, description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/DungeonRoute")
     *     )
     * )
     */
    public function store(
        CombatLogRouteRequest                      $request,
        CombatLogRouteDungeonRouteServiceInterface $combatLogRouteDungeonRouteService,
    ): DungeonRouteResource {
        $validated = $request->validated();

        return new DungeonRouteResource($combatLogRouteDungeonRouteService->convertCombatLogRouteToDungeonRoute(
            CombatLogRouteRequestModel::createFromArray($validated),
        ));
    }

    /**
     * @OA\Post(
     *     operationId="combatLogRouteCorrection",
     *     path="/api/v1/combatlog/route/correct",
     *     summary="Create a new route from a combat log, and correct the events in it",
     *     tags={"CombatLog"},
     *
     *     @OA\RequestBody(
     *           description="Request object containing all parameters required to generate a route from a combat log",
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/CombatLogRouteRequest")
     *      ),
     *
     *     @OA\Response(response=200, description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/CombatLogRouteRequestCorrection")
     *     )
     * )
     */
    public function correctEvents(
        CombatLogRouteRequest                      $request,
        CombatLogRouteDungeonRouteServiceInterface $combatLogRouteDungeonRouteService,
    ): CombatLogRouteCorrectionRequestResource {
        $validated = $request->validated();

        return new CombatLogRouteCorrectionRequestResource($combatLogRouteDungeonRouteService->correctCombatLogRoute(
            CombatLogRouteRequestModel::createFromArray($validated),
        ));
    }
}
