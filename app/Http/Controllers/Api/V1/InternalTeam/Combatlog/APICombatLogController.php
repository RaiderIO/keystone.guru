<?php

namespace App\Http\Controllers\Api\V1\InternalTeam\Combatlog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreateRouteRequest;
use App\Http\Resources\CombatLog\CreateRouteBodyResource;
use App\Http\Resources\DungeonRoute\DungeonRouteResource;
use App\Service\CombatLog\CreateRouteDungeonRouteServiceInterface;
use App\Service\CombatLog\Models\CreateRoute\CreateRouteBody;
use App\Traits\SavesStringToTempDisk;

class APICombatLogController extends Controller
{
    use SavesStringToTempDisk;

    /**
     * @OA\Post(
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
        CreateRouteRequest                      $request,
        CreateRouteDungeonRouteServiceInterface $createRouteBodyDungeonRouteService
    ): DungeonRouteResource {
        $validated = $request->validated();

        return new DungeonRouteResource($createRouteBodyDungeonRouteService->convertCreateRouteBodyToDungeonRoute(
            CreateRouteBody::createFromArray($validated)
        ));
    }

    public function correctEvents(
        CreateRouteRequest                      $request,
        CreateRouteDungeonRouteServiceInterface $createRouteBodyDungeonRouteService
    ): CreateRouteBodyResource {
        $validated = $request->validated();

        return new CreateRouteBodyResource($createRouteBodyDungeonRouteService->correctCreateRouteBody(
            CreateRouteBody::createFromArray($validated)
        ));
    }
}
