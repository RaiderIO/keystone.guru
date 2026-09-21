<?php

namespace App\Service\DungeonRoute\Logging;

interface DungeonRouteSeasonContinuationServiceLoggingInterface
{
    public function continueInNewerSeasonStart(int $sourceDungeonRouteId): void;

    public function continueInNewerSeasonNoContinuationSeason(int $sourceDungeonRouteId): void;

    public function continueInNewerSeasonFailed(int $sourceDungeonRouteId, int $continuationDungeonRouteId): void;

    public function continueInNewerSeasonEnd(int $continuationDungeonRouteId, int $seasonId): void;
}
