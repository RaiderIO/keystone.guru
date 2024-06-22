<?php

namespace App\Http\Controllers\Api\V1\Combatlog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreateRouteRequest;
use App\Http\Resources\CombatLog\CombatLogEventCollectionResource;
use App\Http\Resources\DungeonRoute\DungeonRouteResource;
use App\Service\CombatLog\CreateRouteDungeonRouteServiceInterface;
use App\Service\CombatLog\Models\CreateRoute\CreateRouteBody;
use App\Traits\SavesStringToTempDisk;

class APICombatLogController extends Controller
{
    use SavesStringToTempDisk;

    public function createRoute(
        CreateRouteRequest                      $request,
        CreateRouteDungeonRouteServiceInterface $createRouteBodyDungeonRouteService
    ): DungeonRouteResource {
        $validated = $request->validated();

        return new DungeonRouteResource($createRouteBodyDungeonRouteService->convertCreateRouteBodyToDungeonRoute(
            CreateRouteBody::createFromArray($validated)
        ));
    }

    public function createEvent(
        CreateRouteRequest                      $request,
        CreateRouteDungeonRouteServiceInterface $createRouteBodyDungeonRouteService
    ): CombatLogEventCollectionResource {
        $validated = $request->validated();

        return new CombatLogEventCollectionResource($createRouteBodyDungeonRouteService->convertCreateRouteBodyToCombatLogEvents(
            CreateRouteBody::createFromArray($validated)
        ));
    }
}
