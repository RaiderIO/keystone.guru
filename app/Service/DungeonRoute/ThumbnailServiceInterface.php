<?php


namespace App\Service\DungeonRoute;

use App\Models\DungeonRoute;

interface ThumbnailServiceInterface
{
    /**
     * @param DungeonRoute $dungeonRoute
     * @return void
     */
    function queueThumbnailRefresh(DungeonRoute $dungeonRoute): void;

    /**
     * @param DungeonRoute $dungeonRoute
     * @param int $floorIndex
     * @return void
     */
    function refreshThumbnail(DungeonRoute $dungeonRoute, int $floorIndex): void;

    /**
     * @param DungeonRoute $dungeonRoute
     * @param int $floorIndex
     * @return string
     */
    function getFileName(DungeonRoute $dungeonRoute, int $floorIndex): string;

    /**
     * @param DungeonRoute $dungeonRoute
     * @param int $floorIndex
     * @return string
     */
    function getTargetFilePath(DungeonRoute $dungeonRoute, int $floorIndex): string;

    /**
     * @param DungeonRoute $dungeonRoute
     * @param int $floorIndex
     * @return bool
     */
    public function hasThumbnailForFloor(DungeonRoute $dungeonRoute, int $floorIndex): bool;

    /**
     * @param DungeonRoute $sourceDungeonRoute
     * @param DungeonRoute $targetDungeonRoute
     * @return void
     */
    function copyThumbnails(DungeonRoute $sourceDungeonRoute, DungeonRoute $targetDungeonRoute): bool;

    /**
     * @param DungeonRoute $dungeonRoute
     * @return bool
     */
    public function cleanupThumbnails(DungeonRoute $dungeonRoute): bool;
}
