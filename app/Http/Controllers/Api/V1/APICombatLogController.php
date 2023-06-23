<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreateRouteRequest;
use App\Http\Resources\DungeonRouteResource;
use App\Service\CombatLog\CombatLogDungeonRouteServiceInterface;
use App\Service\CombatLog\Models\CreateRoute\CreateRouteBody;
use App\Traits\SavesStringToTempDisk;

class APICombatLogController extends Controller
{
    use SavesStringToTempDisk;

    /**
     * @param CreateRouteRequest $request
     * @param CombatLogDungeonRouteServiceInterface $combatLogDungeonRouteService
     * @return DungeonRouteResource
     */
    public function createRoute(
        CreateRouteRequest                    $request,
        CombatLogDungeonRouteServiceInterface $combatLogDungeonRouteService
    ): DungeonRouteResource
    {
        $validated = $request->validated();

        dd(CreateRouteBody::createFromArray($validated));

        $targetFile = $this->saveFile('combatlog', $validated['combatlog']);

        $dungeonRoutes = $combatLogDungeonRouteService->convertCombatLogToDungeonRoutes($targetFile);

        return new DungeonRouteResource($dungeonRoutes->first());
    }
}
