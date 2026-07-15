<?php

namespace App\Service\CombatLog\DataExtractors\Logging;

use App\Logging\Concerns\InteractsWithRollbar;
use App\Logging\StructuredLogging;

class FloorDataExtractorLogging extends StructuredLogging implements FloorDataExtractorLoggingInterface
{
    use InteractsWithRollbar;

    public function extractDataUpdatedFloorCoordinates(
        int   $floorId,
        float $newIngameMinX,
        float $newIngameMinY,
        float $newIngameMaxX,
        float $newIngameMaxY,
    ): void {
        $this->debug(__METHOD__, get_defined_vars());
    }

    public function extractDataAddedNewFloorConnection(int $previousFloorId, int $currentFloorId): void
    {
        $this->debug(__METHOD__, get_defined_vars());
    }
}
