<?php

namespace App\Http\Controllers\Api\V1\InternalTeam\Combatlog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CombatLog\EnemyFailure\CombatLogEnemyFailureIndexRequest;
use App\Http\Resources\CombatLog\CombatLogRouteEnemyFailureEnvelopeResource;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Repositories\Interfaces\CombatLog\CombatLogRouteEnemyFailureRepositoryInterface;

class APICombatLogEnemyFailureController extends Controller
{
    /**
     * @OA\Get(
     *     operationId="getCombatLogEnemyFailures",
     *     path="/api/v1/combatlog/enemy-failures/{dungeon}",
     *     summary="List the Auto Route Creator enemy failures recorded for a dungeon, oldest first, cursor-paginated by id",
     *     tags={"CombatLog"},
     *
     *     @OA\Parameter(name="dungeon", in="path", required=true, description="Dungeon slug", @OA\Schema(type="string")),
     *     @OA\Parameter(name="after_id", in="query", required=false, description="Only failures with an id greater than this (use meta.next_after_id of the previous page)", @OA\Schema(type="integer", minimum=0)),
     *     @OA\Parameter(name="limit", in="query", required=false, description="Page size, 1..1000 (default 1000)", @OA\Schema(type="integer", minimum=1, maximum=1000)),
     *     @OA\Parameter(name="mapping_version_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="npc_id[]", in="query", required=false, @OA\Schema(type="array", @OA\Items(type="integer"))),
     *     @OA\Parameter(name="since", in="query", required=false, description="Only failures recorded at or after this date/time", @OA\Schema(type="string", format="date-time")),
     *
     *     @OA\Response(response=200, description="Successful operation", @OA\JsonContent(ref="#/components/schemas/CombatLogRouteEnemyFailureEnvelope")),
     *     @OA\Response(response=403, description="Not an admin"),
     *     @OA\Response(response=404, description="Unknown dungeon"),
     *     @OA\Response(response=422, description="Validation errors"),
     * )
     */
    public function index(
        CombatLogEnemyFailureIndexRequest             $request,
        Dungeon                                       $dungeon,
        CombatLogRouteEnemyFailureRepositoryInterface $combatLogRouteEnemyFailureRepository,
    ): CombatLogRouteEnemyFailureEnvelopeResource {
        $limit = $request->getLimit();

        // The repository fetches one row more than the limit so we know whether a next page exists
        $failures = $combatLogRouteEnemyFailureRepository->getPageAfterId(
            $dungeon,
            $request->getAfterId(),
            $limit,
            $request->getMappingVersionId(),
            $request->getNpcIds(),
            $request->getSince(),
        );

        $hasMore  = $failures->count() > $limit;
        $failures = $failures->take($limit);

        // DungeonRoute lives on the other database connection, so the public keys are resolved in a second query
        $dungeonRouteIds = $failures->pluck('dungeon_route_id')->filter()->unique()->values();
        /** @var array<int, string> $dungeonRoutePublicKeysById */
        $dungeonRoutePublicKeysById = $dungeonRouteIds->isEmpty() ? [] : DungeonRoute::query()
            ->whereIn('id', $dungeonRouteIds)
            ->pluck('public_key', 'id')
            ->all();

        return new CombatLogRouteEnemyFailureEnvelopeResource($failures, $hasMore, $dungeonRoutePublicKeysById);
    }
}
