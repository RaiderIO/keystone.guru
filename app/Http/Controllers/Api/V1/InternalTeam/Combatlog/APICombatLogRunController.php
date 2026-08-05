<?php

namespace App\Http\Controllers\Api\V1\InternalTeam\Combatlog;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ResolvesCombatLogRunSegments;
use App\Http\Requests\Api\V1\CombatLog\Run\CombatLogRunSegmentsRequest;
use App\Service\RaiderIO\RaiderIOApiServiceInterface;
use Illuminate\Http\JsonResponse;

class APICombatLogRunController extends Controller
{
    use ResolvesCombatLogRunSegments;

    /**
     * @OA\Get(
     *     operationId="getCombatLogRunSegments",
     *     path="/api/v1/combatlog/seasons/{season}/runs/{runId}/segments",
     *     summary="Get the Raider.IO log segment download URLs for a run",
     *     tags={"CombatLog"},
     *
     *     @OA\Parameter(name="season", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="runId", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Successful operation")
     * )
     */
    public function segments(
        CombatLogRunSegmentsRequest $request,
        RaiderIOApiServiceInterface $raiderIOApiService,
    ): JsonResponse {
        return $this->resolveCombatLogRunSegments($raiderIOApiService, $request->season(), $request->runId());
    }
}
