<?php

namespace App\Service\CombatLog\Filters;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeEnd;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeStart;
use App\Logic\CombatLog\SpecialEvents\MapChange;
use App\Service\CombatLog\Interfaces\CombatLogParserInterface;
use App\Service\CombatLog\ResultEvents\ChallengeModeEnd as ChallengeModeEndResultEvent;
use App\Service\CombatLog\ResultEvents\ChallengeModeStart as ChallengeModeStartResultEvent;
use App\Service\CombatLog\ResultEvents\MapChange as MapChangeResultEvent;
use Illuminate\Support\Collection;

class SpecialEventsFilter implements CombatLogParserInterface
{
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
     * @param int                            $lineNr
     *
     * @return bool
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
            $this->resultEvents->push((new MapChangeResultEvent($combatLogEvent)));
            
            return true;
        }
        
        return false;
    }

}