<?php

namespace App\Service\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteThumbnail;
use App\Models\DungeonRoute\DungeonRouteThumbnailJob;
use Illuminate\Support\Collection;

interface ThumbnailServiceInterface
{
    public function queueThumbnailRefresh(DungeonRoute $dungeonRoute, bool $force = false): bool;

    /**
     * Queues the larger hero-band thumbnail variant for a route, skipping it when a fresh hero already exists.
     */
    public function queueHeroThumbnailRefresh(DungeonRoute $dungeonRoute, bool $force = false): bool;

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
