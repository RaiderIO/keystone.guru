<?php

namespace App\Http\Controllers\Api\V1\InternalTeam\Combatlog;

use App\Http\Controllers\Controller;
use App\Http\Models\Request\CombatLog\Route\CombatLogRouteRequestModel;
use App\Http\Requests\Api\V1\CombatLog\Route\CombatLogRouteRequest;
use App\Http\Resources\CombatLog\CombatLogRouteResource;
use App\Http\Resources\DungeonRoute\DungeonRouteResource;
use App\Service\CombatLog\CombatLogRouteDungeonRouteServiceInterface;
use App\Traits\SavesStringToTempDisk;

class APICombatLogController extends Controller
{
    use SavesStringToTempDisk;

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
     *          @OA\JsonContent(ref="#/components/schemas/RouteThumbnailRequest")
     *      ),
     *
     *     @OA\Response(response=200, description="Successful operation")
     * )
     */
    public function createRoute(
        CombatLogRouteRequest                   $request,
        CombatLogRouteDungeonRouteServiceInterface $combatLogRouteDungeonRouteService
    ): DungeonRouteResource {
        $validated = $request->validated();

        return new DungeonRouteResource($combatLogRouteDungeonRouteService->convertCombatLogRouteToDungeonRoute(
            CombatLogRouteRequestModel::createFromArray($validated)
        ));
    }

    public function correctEvents(
        CombatLogRouteRequest                   $request,
        CombatLogRouteDungeonRouteServiceInterface $combatLogRouteDungeonRouteService
    ): CombatLogRouteResource {
        $validated = $request->validated();

        return new CombatLogRouteResource($combatLogRouteDungeonRouteService->correctCombatLogRoute(
            CombatLogRouteRequestModel::createFromArray($validated)
        ));
    }
}
