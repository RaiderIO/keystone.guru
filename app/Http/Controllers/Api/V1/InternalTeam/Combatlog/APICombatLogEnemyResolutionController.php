<?php

namespace App\Http\Controllers\Api\V1\InternalTeam\Combatlog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CombatLog\EnemyResolution\CombatLogEnemyResolutionIndexRequest;
use App\Http\Resources\CombatLog\CombatLogRouteEnemyResolutionEnvelopeResource;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Repositories\Interfaces\CombatLog\CombatLogRouteEnemyResolutionRepositoryInterface;

class APICombatLogEnemyResolutionController extends Controller
{
    /**
     * @OA\Get(
     *     operationId="getCombatLogEnemyResolutions",
     *     path="/api/v1/combatlog/enemy-resolutions/{dungeon}",
     *     summary="List the Auto Route Creator enemy matches recorded as suspiciously far off for a dungeon, oldest first, cursor-paginated by id",
     *     tags={"CombatLog"},
     *
     *     @OA\Parameter(name="dungeon", in="path", required=true, description="Dungeon slug", @OA\Schema(type="string")),
     *     @OA\Parameter(name="after_id", in="query", required=false, description="Only resolutions with an id greater than this (use meta.next_after_id of the previous page)", @OA\Schema(type="integer", minimum=0)),
     *     @OA\Parameter(name="limit", in="query", required=false, description="Page size, 1..1000 (default 1000)", @OA\Schema(type="integer", minimum=1, maximum=1000)),
     *     @OA\Parameter(name="mapping_version_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="npc_id[]", in="query", required=false, @OA\Schema(type="array", @OA\Items(type="integer"))),
     *     @OA\Parameter(name="since", in="query", required=false, description="Only resolutions recorded at or after this date/time", @OA\Schema(type="string", format="date-time")),
     *     @OA\Parameter(name="min_distance", in="query", required=false, description="Only resolutions at least this far off, judged on the same kill priority weighted distance the matcher used", @OA\Schema(type="number", format="float", minimum=0)),
     *
     *     @OA\Response(response=200, description="Successful operation", @OA\JsonContent(ref="#/components/schemas/CombatLogRouteEnemyResolutionEnvelope")),
     *     @OA\Response(response=403, description="Not an admin"),
     *     @OA\Response(response=404, description="Unknown dungeon"),
     *     @OA\Response(response=422, description="Validation errors"),
     * )
     */
    public function index(
        CombatLogEnemyResolutionIndexRequest             $request,
        Dungeon                                          $dungeon,
        CombatLogRouteEnemyResolutionRepositoryInterface $combatLogRouteEnemyResolutionRepository,
    ): CombatLogRouteEnemyResolutionEnvelopeResource {
        $limit = $request->getLimit();

        // The repository fetches one row more than the limit so we know whether a next page exists
        $resolutions = $combatLogRouteEnemyResolutionRepository->getPageAfterId(
            $dungeon,
            $request->getAfterId(),
            $limit,
            $request->getMappingVersionId(),
            $request->getNpcIds(),
            $request->getSince(),
            $request->getMinDistance(),
        );

        $hasMore     = $resolutions->count() > $limit;
        $resolutions = $resolutions->take($limit);

        // DungeonRoute lives on the other database connection, so the public keys are resolved in a second query.
        // Only rows this environment recorded itself point at a local route: an imported row keeps the id it had on
        // the deployment it came from, which identifies a different route here whenever the two numbers collide.
        $dungeonRouteIds = $resolutions
            ->where('source', null)
            ->pluck('dungeon_route_id')
            ->filter()
            ->unique()
            ->values();
        /** @var array<int, string> $dungeonRoutePublicKeysById */
        $dungeonRoutePublicKeysById = $dungeonRouteIds->isEmpty() ? [] : DungeonRoute::query()
            ->whereIn('id', $dungeonRouteIds)
            ->pluck('public_key', 'id')
            ->all();

        return new CombatLogRouteEnemyResolutionEnvelopeResource($resolutions, $hasMore, $dungeonRoutePublicKeysById);
    }
}
