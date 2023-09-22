<?php

namespace App\Service\CombatLog\Filters;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\SpecialEvents\CombatLogVersion;
use App\Logic\CombatLog\SpecialEvents\MapChange;
use App\Logic\CombatLog\SpecialEvents\ZoneChange;
use App\Service\CombatLog\Exceptions\DungeonNotSupportedException;
use App\Service\CombatLog\Exceptions\FloorNotSupportedException;
use App\Service\CombatLog\Interfaces\CombatLogParserInterface;
use App\Service\CombatLog\ResultEvents\CombatLogVersion as CombatLogVersionResultEvent;
use App\Service\CombatLog\ResultEvents\MapChange as MapChangeResultEvent;
use App\Service\CombatLog\ResultEvents\ZoneChange as ZoneChangeResultEvent;
use Illuminate\Support\Collection;

abstract class BaseSpecialEventsFilter implements CombatLogParserInterface
{
    private const IGNORE_MAP_IDS = [
        // Northern Barrens
        1,
    ];

    // @TODO should be removed when all floor map UIs have been resolved for all existing dungeons
    private const IGNORE_FLOOR_MAP_UI_IDS = [
        // Wailing Caverns
        11,
        // Kalimdor,
        12,
        // Feralas,
        69,
        // Dire Maul (outside)
        234,
        // Pandaria
        424,
        // Draenor
        572,
        // Broken Isles
        619,
        // Suramar
        680,
        // Eastern Kingdoms
        1415,
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
     * @param int       $lineNr
     *
     * @return bool
     * @throws FloorNotSupportedException
     * @throws DungeonNotSupportedException
     */
    public function parse(BaseEvent $combatLogEvent, int $lineNr): bool
    {
        // Combat log versions yes please
        if( $combatLogEvent instanceof CombatLogVersion ){
            $this->resultEvents->push((new CombatLogVersionResultEvent($combatLogEvent)));

            return true;
        }

        // Zone changes yes please
        if ($combatLogEvent instanceof ZoneChange) {
            try {
                $this->resultEvents->push((new ZoneChangeResultEvent($combatLogEvent)));
            } catch (DungeonNotSupportedException $e) {
                if (!in_array($combatLogEvent->getZoneId(), self::IGNORE_MAP_IDS)) {
                    throw $e;
                }
            }

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
