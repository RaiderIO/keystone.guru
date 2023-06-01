<?php

namespace App\Service\CombatLog\Models\ResultEvents;

use App\Logic\CombatLog\SpecialEvents\MapChange as MapChangeCombatLogEvent;
use App\Models\Floor;
use Exception;

class MapChange extends BaseResultEvent
{
    private Floor $floor;

    public function __construct(MapChangeCombatLogEvent $baseEvent)
    {
        parent::__construct($baseEvent);

        $this->floor = Floor::where('ui_map_id', $baseEvent->getUiMapID())->firstOrFail();
    }

    /**
     * @return Floor
     */
    public function getFloor(): Floor
    {
        return $this->floor;
    }
}
