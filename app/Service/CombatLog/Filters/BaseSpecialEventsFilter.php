<?php

namespace App\Service\CombatLog\Filters;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\SpecialEvents\MapChange;
use App\Logic\CombatLog\SpecialEvents\ZoneChange;
use App\Service\CombatLog\Exceptions\DungeonNotSupportedException;
use App\Service\CombatLog\Exceptions\FloorNotSupportedException;
use App\Service\CombatLog\Interfaces\CombatLogParserInterface;
use App\Service\CombatLog\ResultEvents\MapChange as MapChangeResultEvent;
use App\Service\CombatLog\ResultEvents\ZoneChange as ZoneChangeResultEvent;
use Illuminate\Support\Collection;

abstract class BaseSpecialEventsFilter implements CombatLogParserInterface
{
    // @TODO should be removed when all floor map UIs have been resolved for all existing dungeons
    private const IGNORE_FLOOR_MAP_UI_IDS = [
        // Pandaria
        424,
        // Draenor
        572,
        // Broken Isles
        619,
        // Suramar
        680,
        // The Waking Shores
        2022,
        // Thaldraszus
        2025,
        // Valdrakken
        2112,
    ];

    protected Collection $resultEvents;

    /**
     * @param Collection $resultEvents
     */
    public function __construct(Collection $resultEvents)
    {
        $this->resultEvents = $resultEvents;
    }

    /**
     * @param BaseEvent $combatLogEvent
     * @param int $lineNr
     *
     * @return bool
     * @throws FloorNotSupportedException
     * @throws DungeonNotSupportedException
     */
    public function parse(BaseEvent $combatLogEvent, int $lineNr): bool
    {
        // Zone changes yes please
        if ($combatLogEvent instanceof ZoneChange) {
            $this->resultEvents->push((new ZoneChangeResultEvent($combatLogEvent)));

            return true;
        }

        // Map changes yes please
        if ($combatLogEvent instanceof MapChange) {
            try {
                $this->resultEvents->push((new MapChangeResultEvent($combatLogEvent)));
            } catch (FloorNotSupportedException $e) {
                if (!in_array($combatLogEvent->getUiMapID(), self::IGNORE_FLOOR_MAP_UI_IDS)) {
                    throw $e;
                }
            }

            return true;
        }

        return false;
    }

}
