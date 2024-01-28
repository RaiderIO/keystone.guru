<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\DungeonRouteThumbnailJob\DungeonRouteThumbnailJobResource;
use App\Models\DungeonRoute\DungeonRouteThumbnailJob;
use Illuminate\Auth\Access\AuthorizationException;

class APIDungeonRouteThumbnailJobController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/v1/route/thumbnailJob/{thumbnailJobId}",
     *      summary="Return the status of a thumbnail job",
     *      tags={"Route"},
     *      @OA\Response(response=200, description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/RouteThumbnailJobEnvelope"))
     *     )
     *  )
     *
     * @param DungeonRouteThumbnailJob $dungeonRouteThumbnailJob
     * @return DungeonRouteThumbnailJobResource
     * @throws AuthorizationException
     */
    public function get(
        DungeonRouteThumbnailJob $dungeonRouteThumbnailJob
    ): DungeonRouteThumbnailJobResource {
        $this->authorize('view', $dungeonRouteThumbnailJob->dungeonRoute);

        return new DungeonRouteThumbnailJobResource($dungeonRouteThumbnailJob);
    }
}
