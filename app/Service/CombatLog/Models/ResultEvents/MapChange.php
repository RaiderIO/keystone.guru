<?php

namespace App\Service\CombatLog\Models\ResultEvents;

use App\Logic\CombatLog\SpecialEvents\MapChange as MapChangeCombatLogEvent;
use App\Models\Floor;
use Exception;

class MapChange extends BaseResultEvent
{
    private ?Floor $floor = null;

    public function __construct(MapChangeCombatLogEvent $baseEvent)
    {
        parent::__construct($baseEvent);

        try {
            $this->floor = Floor::where('ui_map_id', $baseEvent->getUiMapID())->firstOrFail();
        } catch (Exception $exception) {
//            throw new Exception(sprintf('Unable to find floor for UI Map %s (%d)', $baseEvent->getUiMapName(), $baseEvent->getUiMapID()));
        }
    }

    /**
     * @return Floor|null
     */
    public function getFloor(): ?Floor
    {
        return $this->floor;
    }
}
