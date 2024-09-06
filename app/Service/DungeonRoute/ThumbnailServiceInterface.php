<?php

namespace App\Service\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteThumbnailJob;
use Illuminate\Support\Collection;

interface ThumbnailServiceInterface
{
    public function queueThumbnailRefresh(DungeonRoute $dungeonRoute): bool;

    /**
     * @return Collection<DungeonRouteThumbnailJob>
     */
    public function queueThumbnailRefreshForApi(
        DungeonRoute $dungeonRoute,
        ?int         $viewportWidth = null,
        ?int         $viewportHeight = null,
        ?int         $imageWidth = null,
        ?int         $imageHeight = null,
        ?int         $zoomLevel = null,
        ?int         $quality = null
    ): Collection;

    public function createThumbnail(
        DungeonRoute $dungeonRoute,
        int          $floorIndex,
        int          $attempts
    ): bool;

    public function createThumbnailCustom(
        DungeonRoute $dungeonRoute,
        int          $floorIndex,
        int          $attempts,
        ?int         $viewportWidth = null,
        ?int         $viewportHeight = null,
        ?int         $imageWidth = null,
        ?int         $imageHeight = null,
        ?int         $zoomLevel = null,
        ?int         $quality = null
    ): bool;

    /**
     * @return void
     */
    public function copyThumbnails(DungeonRoute $sourceDungeonRoute, DungeonRoute $targetDungeonRoute): bool;

    public function hasThumbnailsGenerated(DungeonRoute $dungeonRoute): bool;
}
