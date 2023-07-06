<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreateRouteRequest;
use App\Http\Resources\DungeonRouteResource;
use App\Service\CombatLog\CreateRouteDungeonRouteServiceInterface;
use App\Service\CombatLog\Models\CreateRoute\CreateRouteBody;
use App\Traits\SavesStringToTempDisk;

class APICombatLogController extends Controller
{
    use SavesStringToTempDisk;

    /**
     * @param CreateRouteRequest                      $request
     * @param CreateRouteDungeonRouteServiceInterface $createRouteBodyDungeonRouteService
     *
     * @return DungeonRouteResource
     */
    public function createRoute(
        CreateRouteRequest $request,
        CreateRouteDungeonRouteServiceInterface $createRouteBodyDungeonRouteService
    ): DungeonRouteResource {
        $validated = $request->validated();

        return new DungeonRouteResource($createRouteBodyDungeonRouteService->convertCreateRouteBodyToDungeonRoute(
            CreateRouteBody::createFromArray($validated)
        ));
    }
}
