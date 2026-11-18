<?php

namespace App\Http\Controllers\Api\V1\InternalTeam\WalkGraph;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\WalkGraph\APIWalkGraphShowRequest;
use App\Http\Resources\WalkGraph\WalkGraphResource;
use App\Http\Resources\WalkGraph\WalkGraphSummaryEnvelopeResource;
use App\Repositories\Interfaces\WalkGraph\WalkGraphRepositoryInterface;

/**
 * Read-only export of the walk graphs built from combat logs, so they can be copied into the dungeon seeder files.
 */
class APIWalkGraphController extends Controller
{
    /**
     * @OA\Get(
     *     operationId="getWalkGraphs",
     *     path="/api/v1/walkgraphs",
     *     summary="Every walk graph's metadata and the dungeon floors that use its ui map, without the edges",
     *     tags={"WalkGraph"},
     *
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=403, description="Not an admin or AI agent"),
     * )
     */
    public function index(WalkGraphRepositoryInterface $walkGraphRepository): WalkGraphSummaryEnvelopeResource
    {
        return new WalkGraphSummaryEnvelopeResource($walkGraphRepository->allSummariesWithFloors());
    }

    /**
     * @OA\Get(
     *     operationId="getWalkGraph",
     *     path="/api/v1/walkgraphs/{uiMapId}",
     *     summary="One walk graph, including its cell size and the base64-encoded compressed edge counts",
     *     tags={"WalkGraph"},
     *
     *     @OA\Parameter(name="uiMapId", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=403, description="Not an admin or AI agent"),
     *     @OA\Response(response=404, description="No walk graph for this ui map"),
     * )
     */
    public function show(APIWalkGraphShowRequest $request): WalkGraphResource
    {
        return new WalkGraphResource($request->getWalkGraph());
    }
}
