<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\DungeonRoute\DungeonRouteThumbnailJobResource;
use App\Models\DungeonRoute\DungeonRouteThumbnailJob;
use Illuminate\Auth\Access\AuthorizationException;

class APIDungeonRouteThumbnailJobController extends Controller
{
    /**
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
