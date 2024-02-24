<?php

namespace App\Service\CombatLog\ResultEvents;

use App\Logic\CombatLog\SpecialEvents\MapChange as MapChangeEvent;
use App\Models\Floor\Floor;
use App\Service\CombatLog\Exceptions\FloorNotSupportedException;
use Exception;

class MapChange extends BaseResultEvent
{

    private ?Floor $floor = null;

    public function __construct(MapChangeEvent $baseEvent)
    {
        parent::__construct($baseEvent);


        try {
            $this->floor = Floor::findByUiMapId($baseEvent->getUiMapID());
        } catch (Exception) {
            throw new FloorNotSupportedException(
                sprintf('Floor with ui MAP ID %d not found', $baseEvent->getUiMapID())
            );
        }
    }

    /**
     * @return Floor|null
     */
    public function getFloor(): ?Floor
    {
        return $this->floor;
    }

    /**
     * @return MapChangeEvent
     */
    public function getMapChangeEvent(): MapChangeEvent
    {
        /** @var MapChangeEvent $mapChangeEvent */
        $mapChangeEvent = $this->getBaseEvent();

        return $mapChangeEvent;
    }
}
