<?php

namespace App\Service\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteThumbnail;
use App\Models\DungeonRoute\DungeonRouteThumbnailJob;
use Illuminate\Support\Collection;

interface ThumbnailServiceInterface
{
    public function queueThumbnailRefresh(DungeonRoute $dungeonRoute, bool $force = false): bool;

    public function queueThumbnailRefreshIfMissing(Collection $dungeonRoutes, bool $force = false): bool;

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
        ?int         $quality = null,
    ): Collection;

    public function createThumbnail(
        DungeonRoute $dungeonRoute,
        int          $floorIndex,
        int          $attempts,
    ): ?DungeonRouteThumbnail;

    public function createThumbnailCustom(
        DungeonRoute $dungeonRoute,
        int          $floorIndex,
        int          $attempts,
        ?int         $viewportWidth = null,
        ?int         $viewportHeight = null,
        ?int         $imageWidth = null,
        ?int         $imageHeight = null,
        ?int         $zoomLevel = null,
        ?int         $quality = null,
    ): ?DungeonRouteThumbnail;

    public function copyThumbnails(DungeonRoute $sourceDungeonRoute, DungeonRoute $targetDungeonRoute): ?Collection;

    public function hasThumbnailsGenerated(DungeonRoute $dungeonRoute): bool;
}
