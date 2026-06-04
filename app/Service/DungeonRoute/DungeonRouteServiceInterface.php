<?php

namespace App\Service\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;

interface DungeonRouteServiceInterface
{
    public function updatePopularity(): int;

    public function updateRating(): int;

    public function refreshOutdatedThumbnails(): int;

    public function deleteExpiredDungeonRoutes(): int;

    public function touchRoutesForTeam(int $teamId): int;

    public function upgradeMappingVersion(DungeonRoute $dungeonRoute): void;

    public function publishScheduledDungeonRoutes(): int;
}
