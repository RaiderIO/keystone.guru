<?php

namespace App\Service\CombatLog\DataExtractors\Logging;

interface FloorDataExtractorLoggingInterface
{
    public function extractDataUpdatedFloorCoordinates(int $floorId, float $newIngameMinX, float $newIngameMinY, float $newIngameMaxX, float $newIngameMaxY): void;

    public function extractDataAddedNewFloorConnection(int $previousFloorId, int $currentFloorId): void;
}
