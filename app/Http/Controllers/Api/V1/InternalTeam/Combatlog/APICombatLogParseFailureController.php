<?php

namespace App\Http\Controllers\Api\V1\InternalTeam\Combatlog;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ResolvesCombatLogParseFailureSegments;
use App\Http\Resources\CombatLog\CombatLogParseFailureEnvelopeResource;
use App\Models\CombatLog\CombatLogParseFailure;
use App\Service\RaiderIO\RaiderIOApiServiceInterface;
use Illuminate\Http\JsonResponse;

class APICombatLogParseFailureController extends Controller
{
    use ResolvesCombatLogParseFailureSegments;

    private const int MAX_RESULTS = 500;

    /**
     * @OA\Get(
     *     operationId="getCombatLogParseFailures",
     *     path="/api/v1/combatlog/parse-failures",
     *     summary="Get a list of unresolved combat log parse failures",
     *     tags={"CombatLog"},
     *
     *     @OA\Response(response=200, description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/CombatLogParseFailureEnvelope")
     *     )
     * )
     */
    public function index(): CombatLogParseFailureEnvelopeResource
    {
        return new CombatLogParseFailureEnvelopeResource(
            CombatLogParseFailure::query()
                ->whereNull('resolved_at')
                ->orderByDesc('created_at')
                ->limit(self::MAX_RESULTS)
                ->get(),
        );
    }

    /**
     * @OA\Get(
     *     operationId="getCombatLogParseFailureSegments",
     *     path="/api/v1/combatlog/parse-failures/{parseFailure}/segments",
     *     summary="Get the Raider.IO log segment download URLs for a parse failure's run",
     *     tags={"CombatLog"},
     *
     *     @OA\Parameter(name="parseFailure", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Successful operation")
     * )
     */
    public function segments(RaiderIOApiServiceInterface $raiderIOApiService, CombatLogParseFailure $parseFailure): JsonResponse
    {
        return $this->resolveCombatLogParseFailureSegments($raiderIOApiService, $parseFailure);
    }

    /**
     * @OA\Post(
     *     operationId="resolveCombatLogParseFailure",
     *     path="/api/v1/combatlog/parse-failures/{parseFailure}/resolve",
     *     summary="Mark a combat log parse failure as resolved",
     *     tags={"CombatLog"},
     *
     *     @OA\Parameter(name="parseFailure", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="ok")
     *         )
     *     )
     * )
     */
    public function resolve(CombatLogParseFailure $parseFailure): JsonResponse
    {
        $parseFailure->update(['resolved_at' => now()]);

        return response()->json(['status' => 'ok']);
    }
}
