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
     * Deletes the standard and front page thumbnails (and their files) of routes that were neither edited nor
     * displayed for keystoneguru.thumbnail.expire_inactive_days, and resets their thumbnail timestamps so the
     * next display renders them again. Custom (API) thumbnails are left alone.
     *
     * @param  int|null $limit  The maximum amount of routes to expire; defaults to keystoneguru.thumbnail.expire_inactive_count.
     * @param  bool     $dryRun Only count the routes that would be expired.
     * @return int      The amount of routes that were (or, on a dry run, would be) expired.
     */
    public function expireInactiveThumbnails(?int $limit = null, bool $dryRun = false): int;

    /**
     * Records that the given routes are in the discover hero set right now.
     *
     * @param Collection<int, DungeonRoute> $heroRoutes
     */
    public function markHeroRoutes(Collection $heroRoutes): void;

    /**
     * Deletes the hero and front page thumbnails (and their files) of routes that left the hero set more than
     * keystoneguru.thumbnail.hero_expire_days ago.
     *
     * @return int The amount of thumbnails that were deleted.
     */
    public function expireHeroThumbnailsOutsideHeroSet(): int;

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
