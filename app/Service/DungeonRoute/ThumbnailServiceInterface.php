<?php


namespace App\Service\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;

interface ThumbnailServiceInterface
{
    /**
     * @param DungeonRoute $dungeonRoute
     * @return bool
     */
    public function queueThumbnailRefresh(DungeonRoute $dungeonRoute): bool;

    /**
     * @param DungeonRoute $dungeonRoute
     * @param int          $floorIndex
     * @param int          $attempts
     * @return void
     */
    public function refreshThumbnail(DungeonRoute $dungeonRoute, int $floorIndex, int $attempts): void;

    /**
     * @param DungeonRoute $dungeonRoute
     * @param int          $floorIndex
     * @return string
     */
    public function getFileName(DungeonRoute $dungeonRoute, int $floorIndex): string;

    /**
     * @param DungeonRoute $dungeonRoute
     * @param int          $floorIndex
     * @return string
     */
    public function getTargetFilePath(DungeonRoute $dungeonRoute, int $floorIndex): string;

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
