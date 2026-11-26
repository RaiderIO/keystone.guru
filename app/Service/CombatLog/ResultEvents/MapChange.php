<?php

namespace App\Service\CombatLog\ResultEvents;

use App\Logic\CombatLog\SpecialEvents\MapChange as MapChangeEvent;
use App\Models\Floor\Floor;
use App\Repositories\Interfaces\Floor\FloorRepositoryInterface;
use App\Service\CombatLog\Exceptions\FloorNotSupportedException;
use Illuminate\Support\Collection;

class MapChange extends BaseResultEvent
{
    /** @var Collection<int, Floor> Every floor carrying the event's ui_map_id, in any dungeon */
    private readonly Collection $candidateFloors;

    /**
     * @throws FloorNotSupportedException
     */
    public function __construct(MapChangeEvent $baseEvent)
    {
        parent::__construct($baseEvent);

        $this->candidateFloors = app(FloorRepositoryInterface::class)->findAllByUiMapIdInAnyDungeon($baseEvent->getUiMapID());
        if ($this->candidateFloors->isEmpty()) {
            throw new FloorNotSupportedException(
                sprintf('Floor with ui MAP ID %d not found', $baseEvent->getUiMapID()),
            );
        }
    }

    /**
     * The dungeon's floor the party walked onto, or null when the ui_map_id belongs to other dungeons only.
     */
    public function getFloorInDungeon(int $dungeonId): ?Floor
    {
        return $this->candidateFloors->firstWhere('dungeon_id', $dungeonId);
    }

    public function getMapChangeEvent(): MapChangeEvent
    {
        /** @var MapChangeEvent $mapChangeEvent */
        $mapChangeEvent = $this->getBaseEvent();

        return $mapChangeEvent;
    }
}
