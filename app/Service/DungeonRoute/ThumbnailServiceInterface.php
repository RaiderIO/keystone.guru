<?php


namespace App\Service\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteThumbnailJob;
use Illuminate\Support\Collection;

interface ThumbnailServiceInterface
{
    /**
     * @param DungeonRoute $dungeonRoute
     * @return bool
     */
    public function queueThumbnailRefresh(DungeonRoute $dungeonRoute): bool;

    /**
     * @param DungeonRoute $dungeonRoute
     * @param int|null     $width
     * @param int|null     $height
     * @param int|null     $quality
     * @return Collection|DungeonRouteThumbnailJob[]
     */
    public function queueThumbnailRefreshForApi(DungeonRoute $dungeonRoute, ?int $width = null, ?int $height = null, ?int $quality = null): Collection;

    /**
     * @param DungeonRoute $dungeonRoute
     * @param int          $floorIndex
     * @param int          $attempts
     * @return bool
     */
    public function createThumbnail(
        DungeonRoute $dungeonRoute,
        int          $floorIndex,
        int          $attempts
    ): bool;

    /**
     * @param DungeonRoute $dungeonRoute
     * @param int          $floorIndex
     * @param int          $attempts
     * @param int|null     $width
     * @param int|null     $height
     * @param int|null     $quality
     * @return bool
     */
    public function createThumbnailCustom(
        DungeonRoute $dungeonRoute,
        int          $floorIndex,
        int          $attempts,
        ?int         $width = null,
        ?int         $height = null,
        ?int         $quality = null
    ): bool;

    /**
     * @param DungeonRoute $dungeonRoute
     * @param int          $floorIndex
     * @param string       $extension
     * @return string
     */
    public function getFileName(DungeonRoute $dungeonRoute, int $floorIndex, string $extension): string;

    /**
     * @param DungeonRoute $dungeonRoute
     * @param int          $floorIndex
     * @param string       $targetFolder
     * @param string       $extension
     * @return string
     */
    public function getTargetFilePath(DungeonRoute $dungeonRoute, int $floorIndex, string $targetFolder, string $extension): string;

    /**
     * @param DungeonRoute $sourceDungeonRoute
     * @param DungeonRoute $targetDungeonRoute
     * @return void
     */
    public function copyThumbnails(DungeonRoute $sourceDungeonRoute, DungeonRoute $targetDungeonRoute): bool;

    /**
     * @param DungeonRoute $dungeonRoute
     * @return bool
     */
    public function hasThumbnailsGenerated(DungeonRoute $dungeonRoute): bool;
}
