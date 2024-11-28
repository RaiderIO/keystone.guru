<?php

namespace App\Http\Controllers\Api\V1\Public\Route;

use App\Http\Controllers\Controller;
use App\Http\Resources\DungeonRouteThumbnailJob\DungeonRouteThumbnailJobResource;
use App\Models\DungeonRoute\DungeonRouteThumbnailJob;
use Illuminate\Auth\Access\AuthorizationException;

class APIDungeonRouteThumbnailJobController extends Controller
{
    /**
     * @OA\Get(
     *      operationId="getThumbnailJob",
     *      path="/api/v1/route/thumbnailJob/{thumbnailJobId}",
     *      summary="Return the status of a thumbnail job",
     *      tags={"Route"},
     *
     *      @OA\Parameter(
     *          description="The ID of the thumbnail job you want to retrieve",
     *          in="path",
     *          name="thumbnailJobId",
     *          required=true,
     *
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *
     *     @OA\Response(response=200, description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/RouteThumbnailJobEnvelope")
     *     )
     * )
     *
     * @throws AuthorizationException
     */
    public function get(
        DungeonRouteThumbnailJob $dungeonRouteThumbnailJob
    ): DungeonRouteThumbnailJobResource {
        $this->authorize('view', $dungeonRouteThumbnailJob->dungeonRoute);

        return new DungeonRouteThumbnailJobResource($dungeonRouteThumbnailJob);
    }
}
