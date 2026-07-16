<?php

namespace App\Service\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteThumbnail;
use App\Models\DungeonRoute\DungeonRouteThumbnailJob;
use Illuminate\Support\Collection;

interface ThumbnailServiceInterface
{
    /**
     * Queues a thumbnail refresh for the given variant. The standard variant honours $force and records the
     * queued-at timestamp; other variants (e.g. the expensive hero render) skip routes that are already fresh
     * and always force the render.
     */
    public function queueThumbnailRefresh(
        DungeonRoute $dungeonRoute,
        bool         $force = false,
        string       $variant = DungeonRouteThumbnail::VARIANT_STANDARD,
    ): bool;

    /**
     * @param Collection<int, DungeonRoute> $dungeonRoutes
     */
    public function queueThumbnailRefreshIfMissing(Collection $dungeonRoutes, bool $force = false): bool;

    /**
     * @return Collection<int, DungeonRouteThumbnailJob>
     */
    public function queueThumbnailRefreshForApi(
        DungeonRoute $dungeonRoute,
        ?int         $viewportWidth = null,
        ?int         $viewportHeight = null,
        ?int         $imageWidth = null,
        ?int         $imageHeight = null,
        ?float       $zoomLevel = null,
        ?int         $quality = null,
    ): Collection;

    public function createThumbnail(
        DungeonRoute $dungeonRoute,
        int          $floorIndex,
        int          $attempts,
        string       $variant = DungeonRouteThumbnail::VARIANT_STANDARD,
    ): ?DungeonRouteThumbnail;

    public function createThumbnailCustom(
        DungeonRoute $dungeonRoute,
        int          $floorIndex,
        int          $attempts,
        ?int         $viewportWidth = null,
        ?int         $viewportHeight = null,
        ?int         $imageWidth = null,
        ?int         $imageHeight = null,
        ?float       $zoomLevel = null,
        ?int         $quality = null,
    ): ?DungeonRouteThumbnail;

    /**
     * @return Collection<int, DungeonRouteThumbnail>|null
     */
    public function copyThumbnails(DungeonRoute $sourceDungeonRoute, DungeonRoute $targetDungeonRoute): ?Collection;

    public function hasThumbnailsGenerated(DungeonRoute $dungeonRoute): bool;
}
