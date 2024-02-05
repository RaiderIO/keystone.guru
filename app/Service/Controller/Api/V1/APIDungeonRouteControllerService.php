<?php

namespace App\Service\Controller\Api\V1;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteThumbnailJob;
use App\Service\DungeonRoute\ThumbnailServiceInterface;
use Illuminate\Support\Collection;

class APIDungeonRouteControllerService implements APIDungeonRouteControllerServiceInterface
{

    private ThumbnailServiceInterface $thumbnailService;

    /**
     * @param ThumbnailServiceInterface $thumbnailService
     */
    public function __construct(ThumbnailServiceInterface $thumbnailService)
    {
        $this->thumbnailService = $thumbnailService;
    }

    /**
     * @param DungeonRoute $dungeonRoute
     * @param int|null     $width
     * @param int|null     $height
     * @param int|null     $zoomLevel
     * @param int|null     $quality
     * @return Collection|DungeonRouteThumbnailJob[]
     */
    public function createThumbnails(DungeonRoute $dungeonRoute, ?int $width = null, ?int $height = null, ?int $zoomLevel = null, ?int $quality = null): Collection
    {
        return $this->thumbnailService->queueThumbnailRefreshForApi($dungeonRoute, $width, $height, $zoomLevel, $quality);
    }
}
