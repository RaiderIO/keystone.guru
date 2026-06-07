<?php

namespace App\Http\Controllers\Api\V1\Public\Route;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\APIOffsetPaginatedRequest;
use App\Http\Resources\DungeonRoute\DungeonRouteSummaryEnvelopeResource;
use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Service\DungeonRoute\DiscoverServiceInterface;
use Illuminate\Database\Eloquent\Builder;

class APIDungeonRouteDiscoverController extends Controller
{
    /**
     * @OA\Get(
     *     operationId="getPopularRoutesByGameVersion",
     *     path="/api/v1/route/{gameVersion}/popular",
     *     summary="Get popular routes for a game version",
     *     tags={"Route"},
     *
     *     @OA\Parameter(name="gameVersion", in="path", required=true, @OA\Schema(type="string", example="retail")),
     *     @OA\Parameter(name="offset", in="query", required=false, @OA\Schema(type="integer", minimum=0, default=0)),
     *     @OA\Parameter(name="count", in="query", required=false, @OA\Schema(type="integer", minimum=1, maximum=100, default=10)),
     *
     *     @OA\Response(response=200, description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/DungeonRouteSummaryEnvelope")
     *     )
     * )
     */
    public function popular(
        APIOffsetPaginatedRequest $request,
        GameVersion               $gameVersion,
        DiscoverServiceInterface  $discoverService,
    ): DungeonRouteSummaryEnvelopeResource {
        return new DungeonRouteSummaryEnvelopeResource(
            $discoverService
                ->withCache(false)
                ->withGameVersion($gameVersion)
                ->withLimit($request->getCount())
                ->withBuilder(fn(Builder $b) => $b->offset($request->getOffset()))
                ->popular(),
        );
    }

    /**
     * @OA\Get(
     *     operationId="getNewRoutesByGameVersion",
     *     path="/api/v1/route/{gameVersion}/new",
     *     summary="Get new routes for a game version",
     *     tags={"Route"},
     *
     *     @OA\Parameter(name="gameVersion", in="path", required=true, @OA\Schema(type="string", example="retail")),
     *     @OA\Parameter(name="offset", in="query", required=false, @OA\Schema(type="integer", minimum=0, default=0)),
     *     @OA\Parameter(name="count", in="query", required=false, @OA\Schema(type="integer", minimum=1, maximum=100, default=10)),
     *
     *     @OA\Response(response=200, description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/DungeonRouteSummaryEnvelope")
     *     )
     * )
     */
    public function new(
        APIOffsetPaginatedRequest $request,
        GameVersion               $gameVersion,
        DiscoverServiceInterface  $discoverService,
    ): DungeonRouteSummaryEnvelopeResource {
        return new DungeonRouteSummaryEnvelopeResource(
            $discoverService
                ->withCache(false)
                ->withGameVersion($gameVersion)
                ->withLimit($request->getCount())
                ->withBuilder(fn(Builder $b) => $b->offset($request->getOffset()))
                ->new(),
        );
    }

    /**
     * @OA\Get(
     *     operationId="getPopularRoutesByGameVersionAndDungeon",
     *     path="/api/v1/route/{gameVersion}/{dungeon}/popular",
     *     summary="Get popular routes for a specific dungeon",
     *     tags={"Route"},
     *
     *     @OA\Parameter(name="gameVersion", in="path", required=true, @OA\Schema(type="string", example="retail")),
     *     @OA\Parameter(name="dungeon", in="path", required=true, @OA\Schema(type="string", example="ara-kara-city-of-echoes")),
     *     @OA\Parameter(name="offset", in="query", required=false, @OA\Schema(type="integer", minimum=0, default=0)),
     *     @OA\Parameter(name="count", in="query", required=false, @OA\Schema(type="integer", minimum=1, maximum=100, default=10)),
     *
     *     @OA\Response(response=200, description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/DungeonRouteSummaryEnvelope")
     *     )
     * )
     */
    public function dungeonPopular(
        APIOffsetPaginatedRequest $request,
        GameVersion               $gameVersion,
        Dungeon                   $dungeon,
        DiscoverServiceInterface  $discoverService,
    ): DungeonRouteSummaryEnvelopeResource {
        return new DungeonRouteSummaryEnvelopeResource(
            $discoverService
                ->withCache(false)
                ->withGameVersion($gameVersion)
                ->withLimit($request->getCount())
                ->withBuilder(fn(Builder $b) => $b->offset($request->getOffset()))
                ->popularByDungeon($dungeon),
        );
    }

    /**
     * @OA\Get(
     *     operationId="getNewRoutesByGameVersionAndDungeon",
     *     path="/api/v1/route/{gameVersion}/{dungeon}/new",
     *     summary="Get new routes for a specific dungeon",
     *     tags={"Route"},
     *
     *     @OA\Parameter(name="gameVersion", in="path", required=true, @OA\Schema(type="string", example="retail")),
     *     @OA\Parameter(name="dungeon", in="path", required=true, @OA\Schema(type="string", example="ara-kara-city-of-echoes")),
     *     @OA\Parameter(name="offset", in="query", required=false, @OA\Schema(type="integer", minimum=0, default=0)),
     *     @OA\Parameter(name="count", in="query", required=false, @OA\Schema(type="integer", minimum=1, maximum=100, default=10)),
     *
     *     @OA\Response(response=200, description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/DungeonRouteSummaryEnvelope")
     *     )
     * )
     */
    public function dungeonNew(
        APIOffsetPaginatedRequest $request,
        GameVersion               $gameVersion,
        Dungeon                   $dungeon,
        DiscoverServiceInterface  $discoverService,
    ): DungeonRouteSummaryEnvelopeResource {
        return new DungeonRouteSummaryEnvelopeResource(
            $discoverService
                ->withCache(false)
                ->withGameVersion($gameVersion)
                ->withLimit($request->getCount())
                ->withBuilder(fn(Builder $b) => $b->offset($request->getOffset()))
                ->newByDungeon($dungeon),
        );
    }
}
