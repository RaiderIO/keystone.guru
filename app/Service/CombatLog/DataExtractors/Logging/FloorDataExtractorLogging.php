<?php

namespace App\Service\CombatLog\DataExtractors\Logging;

use App\Logging\RollbarStructuredLogging;

class FloorDataExtractorLogging extends RollbarStructuredLogging implements FloorDataExtractorLoggingInterface
{
    public function extractDataUpdatedFloorCoordinates(
        int   $floorId,
        float $newIngameMinX,
        float $newIngameMinY,
        float $newIngameMaxX,
        float $newIngameMaxY
    ): void {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function extractDataAddedNewFloorConnection(int $previousFloorId, int $currentFloorId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }
}
