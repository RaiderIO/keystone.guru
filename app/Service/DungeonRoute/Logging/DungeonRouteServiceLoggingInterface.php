<?php

namespace App\Service\DungeonRoute\Logging;

interface DungeonRouteServiceLoggingInterface
{

    public function updatePopularityStart(): void;

    public function updatePopularityEnd(int $updatedRouteCount): void;

    public function updateRatingStart(): void;

    public function updateRatingEnd(int $updatedRouteCount): void;

    public function refreshOutdatedThumbnailsStart(): void;

    public function refreshOutdatedThumbnailsEnd(int $queuedRouteCount): void;

    public function deleteOutdatedDungeonRoutesStart(): void;

    public function deleteOutdatedDungeonRouteException(int $dungeonRouteId, \Exception $ex): void;

    public function deleteOutdatedDungeonRoutesEnd(int $deletedRouteCount): void;

    public function touchRoutesForTeamStart(int $teamId): void;

    public function touchRoutesForTeamEnd(int $teamId, int $updatedRouteCount): void;
}
