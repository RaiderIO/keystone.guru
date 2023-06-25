<?php

namespace App\Service\CombatLog\ResultEvents;

use App\Logic\CombatLog\SpecialEvents\MapChange as MapChangeEvent;
use App\Models\Floor;
use Exception;

class MapChange extends BaseResultEvent
{

    private ?Floor $floor = null;

    public function __construct(MapChangeEvent $baseEvent)
    {
        parent::__construct($baseEvent);

        $this->floor = Floor::findByUiMapId($baseEvent->getUiMapID());
    }

    /**
     * @return Floor|null
     */
    public function getFloor(): ?Floor
    {
//        if ($this->floor === null) {
//            dd();
//        }
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
