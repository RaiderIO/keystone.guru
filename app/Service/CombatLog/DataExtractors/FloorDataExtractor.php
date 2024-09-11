<?php

namespace App\Service\CombatLog\DataExtractors;

use App;
use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\SpecialEvents\MapChange;
use App\Models\Floor\Floor;
use App\Service\CombatLog\DataExtractors\Logging\FloorDataExtractorLoggingInterface;
use App\Service\CombatLog\Dtos\DataExtraction\DataExtractionCurrentDungeon;
use App\Service\CombatLog\Dtos\DataExtraction\ExtractedDataResult;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class FloorDataExtractor implements DataExtractorInterface
{
//    private ?Floor $previousFloor = null;
    private ?Floor $currentFloor  = null;

    private FloorDataExtractorLoggingInterface $log;

    public function __construct()
    {
        $log = App::make(FloorDataExtractorLoggingInterface::class);
        /** @var FloorDataExtractorLoggingInterface $log */

        $this->log = $log;
    }

    public function beforeExtract(ExtractedDataResult $result, string $combatLogFilePath): void
    {

    }

    public function extractData(ExtractedDataResult $result, DataExtractionCurrentDungeon $currentDungeon, BaseEvent $parsedEvent): void
    {
        if (!($parsedEvent instanceof MapChange)) {
            // Don't log anything because that'd just spam the hell out of it
            return;
        }

//        $this->previousFloor = $this->currentFloor;

        try {
            $this->currentFloor = Floor::findByUiMapId($parsedEvent->getUiMapID(), $currentDungeon->dungeon->id);

            $newIngameMinX = round($parsedEvent->getXMin(), 2);
            $newIngameMinY = round($parsedEvent->getYMin(), 2);
            $newIngameMaxX = round($parsedEvent->getXMax(), 2);
            $newIngameMaxY = round($parsedEvent->getYMax(), 2);

            // Ensure we have the correct bounds for a floor while we're at it
            if ($newIngameMinX !== $this->currentFloor->ingame_min_x || $newIngameMinY !== $this->currentFloor->ingame_min_y ||
                $newIngameMaxX !== $this->currentFloor->ingame_max_x || $newIngameMaxY !== $this->currentFloor->ingame_max_y) {
                $this->currentFloor->update([
                    'ingame_min_x' => $newIngameMinX,
                    'ingame_min_y' => $newIngameMinY,
                    'ingame_max_x' => $newIngameMaxX,
                    'ingame_max_y' => $newIngameMaxY,
                ]);
                $result->updatedFloor();

                $this->log->extractDataUpdatedFloorCoordinates(
                    $this->currentFloor->id,
                    $newIngameMinX,
                    $newIngameMinY,
                    $newIngameMaxX,
                    $newIngameMaxY
                );
            }

            // This doesn't always give me the correct results, so I'm disabling it for now
//            if ($this->previousFloor !== null && $this->previousFloor !== $this->currentFloor) {
//                $assignedFloor = $this->previousFloor->ensureConnectionToFloor($this->currentFloor);
//                $assignedFloor = $this->currentFloor->ensureConnectionToFloor($this->previousFloor) || $assignedFloor;
//
//                if ($assignedFloor) {
//                    $result->updatedFloorConnection();
//
//                    $this->log->extractDataAddedNewFloorConnection(
//                        $this->previousFloor->id,
//                        $this->currentFloor->id
//                    );
//                }
//            }
        } catch (ModelNotFoundException $exception) {
            // We're now on an unknown floor - so don't try to connect floors to floors that aren't actually connected
            $this->currentFloor = null;
        }
    }

    public function afterExtract(ExtractedDataResult $result, string $combatLogFilePath): void
    {
        $this->currentFloor  = null;
//        $this->previousFloor = null;
    }
}
