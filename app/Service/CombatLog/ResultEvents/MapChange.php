<?php

namespace App\Service\CombatLog\ResultEvents;

use App\Logic\CombatLog\SpecialEvents\MapChange as MapChangeEvent;
use App\Models\Floor\Floor;
use App\Service\CombatLog\Exceptions\FloorNotSupportedException;

class MapChange extends BaseResultEvent
{
    private ?Floor $floor = null;

    /**
     * @throws FloorNotSupportedException
     */
    public function __construct(MapChangeEvent $baseEvent)
    {
        parent::__construct($baseEvent);

        $this->floor = Floor::findByUiMapId($baseEvent->getUiMapID());
        if ($this->floor === null) {
            throw new FloorNotSupportedException(
                sprintf('Floor with ui MAP ID %d not found', $baseEvent->getUiMapID())
            );
        }
    }

    public function getFloor(): ?Floor
    {
        return $this->floor;
    }

    public function getMapChangeEvent(): MapChangeEvent
    {
        /** @var MapChangeEvent $mapChangeEvent */
        $mapChangeEvent = $this->getBaseEvent();

        return $mapChangeEvent;
    }
}
