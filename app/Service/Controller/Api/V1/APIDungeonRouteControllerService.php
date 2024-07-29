<?php

namespace App\Service\Controller\Api\V1;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteThumbnailJob;
use App\Service\DungeonRoute\ThumbnailServiceInterface;
use Illuminate\Support\Collection;

class APIDungeonRouteControllerService implements APIDungeonRouteControllerServiceInterface
{
    public function __construct(private readonly ThumbnailServiceInterface $thumbnailService)
    {
    }

    /**
     * @return Collection<DungeonRouteThumbnailJob>
     */
    public function createThumbnails(DungeonRoute $dungeonRoute,
                                     ?int         $viewportWidth = null,
                                     ?int         $viewportHeight = null,
                                     ?int         $imageWidth = null,
                                     ?int         $imageHeight = null,
                                     ?int         $zoomLevel = null,
                                     ?int         $quality = null
    ): Collection {
        return $this->thumbnailService->queueThumbnailRefreshForApi(
            $dungeonRoute,
            $viewportWidth,
            $viewportHeight,
            $imageWidth,
            $imageHeight,
            $zoomLevel,
            $quality
        );
    }
}
