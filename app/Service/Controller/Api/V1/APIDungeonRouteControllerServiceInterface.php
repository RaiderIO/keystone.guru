<?php

namespace App\Service\Controller\Api\V1;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteThumbnailJob;
use Illuminate\Support\Collection;

interface APIDungeonRouteControllerServiceInterface
{
    /**
     * @return Collection|DungeonRouteThumbnailJob[]
     */
    public function createThumbnails(
        DungeonRoute $dungeonRoute,
        ?int         $viewportWidth = null,
        ?int         $viewportHeight = null,
        ?int         $imageWidth = null,
        ?int         $imageHeight = null,
        ?int         $zoomLevel = null,
        ?int         $quality = null
    ): Collection;
}
