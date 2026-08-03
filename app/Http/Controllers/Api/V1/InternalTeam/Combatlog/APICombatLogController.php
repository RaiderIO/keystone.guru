<?php

namespace App\Http\Controllers\Api\V1\InternalTeam\Combatlog;

use App\Dto\Request\CombatLog\Route\CombatLogRouteRequestDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CombatLog\Route\CombatLogRouteRequest;
use App\Http\Resources\CombatLog\Route\CombatLogRouteCorrectionRequestResource;
use App\Http\Resources\DungeonRoute\DungeonRouteResource;
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
            CombatLogRouteRequestDTO::createFromArray($validated),
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
            CombatLogRouteRequestDTO::createFromArray($validated),
        ));
    }
}
