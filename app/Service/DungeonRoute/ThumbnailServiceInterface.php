<?php

namespace App\Service\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteThumbnail;
use App\Models\DungeonRoute\DungeonRouteThumbnailJob;
use App\Models\DungeonRoute\DungeonRouteThumbnailVariant;
use Illuminate\Support\Collection;

interface ThumbnailServiceInterface
{
    /**
     * Queues a thumbnail refresh for the given variant. The standard variant honours $force and records the
     * queued-at timestamp; other variants (e.g. the expensive hero render) skip routes that are already fresh
     * and always force the render.
     */
    public function queueThumbnailRefresh(
        DungeonRoute                 $dungeonRoute,
        bool                         $force = false,
        DungeonRouteThumbnailVariant $variant = DungeonRouteThumbnailVariant::Standard,
    ): bool;

    /**
     * Call for every set of routes whose thumbnails are shown to a visitor: records the access (at most once per
     * route per day) and queues a render for any of them whose thumbnail is stale or missing.
     *
     * @param  Collection<int, DungeonRoute> $dungeonRoutes
     * @return bool                          True when a render was queued for at least one route.
     */
    public function dungeonRoutesDisplayed(Collection $dungeonRoutes): bool;

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

    /**
     * @param bool $isFinalAttempt False while the caller will retry a failed render; the failure is then logged
     *                             as a warning instead of an error.
     */
    public function createThumbnail(
        DungeonRoute                 $dungeonRoute,
        int                          $floorIndex,
        int                          $attempts,
        DungeonRouteThumbnailVariant $variant = DungeonRouteThumbnailVariant::Standard,
        bool                         $isFinalAttempt = true,
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
