<?php

namespace App\Service\DungeonRoute\Logging;

use App\Logging\RollbarStructuredLogging;

class DungeonRouteServiceLogging extends RollbarStructuredLogging implements DungeonRouteServiceLoggingInterface
{
    public function updatePopularityStart(): void
    {
        $this->start(__METHOD__);
    }

    public function updatePopularityEnd(int $updatedRouteCount): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }

    public function updateRatingStart(): void
    {
        $this->start(__METHOD__);
    }

    public function updateRatingEnd(int $updatedRouteCount): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }

    public function refreshOutdatedThumbnailsStart(): void
    {
        $this->start(__METHOD__);
    }

    public function refreshOutdatedThumbnailsEnd(int $queuedRouteCount): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }

    public function deleteOutdatedDungeonRoutesStart(): void
    {
        $this->start(__METHOD__);
    }

    public function deleteOutdatedDungeonRouteException(int $dungeonRouteId, \Exception $ex): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function deleteOutdatedDungeonRoutesEnd(int $deletedRouteCount): void
    {
        $this->end(__METHOD__, get_defined_vars());
    }


}
