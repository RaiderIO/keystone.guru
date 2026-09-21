<?php

namespace App\Service\DungeonRoute\Logging;

use App\Logging\Concerns\InteractsWithRollbar;
use App\Logging\StructuredLogging;

class DungeonRouteSeasonContinuationServiceLogging extends StructuredLogging implements DungeonRouteSeasonContinuationServiceLoggingInterface
{
    use InteractsWithRollbar;

    public function continueInNewerSeasonStart(int $sourceDungeonRouteId): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function continueInNewerSeasonNoContinuationSeason(int $sourceDungeonRouteId): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function continueInNewerSeasonFailed(int $sourceDungeonRouteId, int $continuationDungeonRouteId): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function continueInNewerSeasonEnd(int $continuationDungeonRouteId, int $seasonId): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }
}
