<?php

namespace App\Service\CombatLog\ResultEvents;

use App\Logic\CombatLog\SpecialEvents\MapChange as MapChangeEvent;
use App\Models\Floor;
use Exception;

class MapChange extends BaseResultEvent
{
    // Can map certain floors to others here, so that we can put enemies that are on their own floor (like some final
    // bosses) and put them on the main floor without introducing a 2nd floor.
    private const UI_MAP_ID_MAPPING = [
        // Court of Stars
        763 => 761,
    ];

    private ?Floor $floor = null;

    public function __construct(MapChangeEvent $baseEvent)
    {
        parent::__construct($baseEvent);

        try {
            $this->floor = Floor
                ::where('ui_map_id', self::UI_MAP_ID_MAPPING[$baseEvent->getUiMapID()] ?? $baseEvent->getUiMapID())
                ->firstOrFail();
        } catch (Exception $exception) {
//            dump(sprintf('Unable to find floor for UI Map %s (%d)', $baseEvent->getUiMapName(), $baseEvent->getUiMapID()));
//            throw new Exception(sprintf('Unable to find floor for UI Map %s (%d)', $baseEvent->getUiMapName(), $baseEvent->getUiMapID()));
        }
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
