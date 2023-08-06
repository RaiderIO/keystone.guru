<?php

namespace App\Service\CombatLog\Filters\DungeonRoute;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeEnd;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeStart;
use App\Logic\CombatLog\SpecialEvents\MapChange;
use App\Service\CombatLog\Exceptions\DungeonNotSupportedException;
use App\Service\CombatLog\Exceptions\FloorNotSupportedException;
use App\Service\CombatLog\Interfaces\CombatLogParserInterface;
use App\Service\CombatLog\ResultEvents\ChallengeModeEnd as ChallengeModeEndResultEvent;
use App\Service\CombatLog\ResultEvents\ChallengeModeStart as ChallengeModeStartResultEvent;
use App\Service\CombatLog\ResultEvents\MapChange as MapChangeResultEvent;
use Illuminate\Support\Collection;

class SpecialEventsFilter implements CombatLogParserInterface
{
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

    private Collection $resultEvents;

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
     * @throws DungeonNotSupportedException
     * @throws FloorNotSupportedException
     */
    public function parse(BaseEvent $combatLogEvent, int $lineNr): bool
    {
        // Starts
        if ($combatLogEvent instanceof ChallengeModeStart) {
            $this->resultEvents->push((new ChallengeModeStartResultEvent($combatLogEvent)));

            return true;
        } // Ends
        elseif ($combatLogEvent instanceof ChallengeModeEnd) {
            $this->resultEvents->push((new ChallengeModeEndResultEvent($combatLogEvent)));

            return true;
        } // Map changes yes please
        elseif ($combatLogEvent instanceof MapChange) {
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
