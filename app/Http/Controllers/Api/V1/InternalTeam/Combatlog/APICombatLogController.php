<?php

namespace App\Http\Controllers\Api\V1\InternalTeam\Combatlog;

use App\Dto\Request\CombatLog\Route\CombatLogRouteRequestDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CombatLog\Route\CombatLogRouteRequest;
use App\Http\Resources\CombatLog\Route\CombatLogRouteCorrectionRequestResource;
use App\Http\Resources\DungeonRoute\DungeonRouteResource;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Service\CombatLog\CombatLogRouteDungeonRouteServiceInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;

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
     *     ),
     *     @OA\Response(response=403, description="settings.publicKey names a combat log route the caller did not author")
     * )
     *
     * @throws AuthorizationException
     */
    public function store(
        CombatLogRouteRequest                      $request,
        CombatLogRouteDungeonRouteServiceInterface $combatLogRouteDungeonRouteService,
        DungeonRouteRepositoryInterface            $dungeonRouteRepository,
    ): DungeonRouteResource {
        $validated      = $request->validated();
        $combatLogRoute = CombatLogRouteRequestDto::createFromArray($validated);
        $routeToReplace = $dungeonRouteRepository->findCombatLogRouteByPublicKey($combatLogRoute->settings->publicKey);

        if ($routeToReplace !== null) {
            Gate::authorize('regenerateFromCombatLog', $routeToReplace);
        }

        return new DungeonRouteResource($combatLogRouteDungeonRouteService->convertCombatLogRouteToDungeonRoute(
            $combatLogRoute,
            $request->user(),
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
            CombatLogRouteRequestDto::createFromArray($validated),
        ));
    }
}
