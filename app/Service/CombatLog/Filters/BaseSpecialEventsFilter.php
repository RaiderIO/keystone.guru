<?php

namespace App\Service\CombatLog\Filters;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\SpecialEvents\CombatantInfo\CombatantInfoInterface;
use App\Logic\CombatLog\SpecialEvents\CombatLogVersion;
use App\Logic\CombatLog\SpecialEvents\MapChange;
use App\Logic\CombatLog\SpecialEvents\ZoneChange;
use App\Service\CombatLog\Exceptions\DungeonNotSupportedException;
use App\Service\CombatLog\Exceptions\FloorNotSupportedException;
use App\Service\CombatLog\Interfaces\CombatLogParserInterface;
use App\Service\CombatLog\ResultEvents\BaseResultEvent;
use App\Service\CombatLog\ResultEvents\CombatantInfo as CombatantInfoResultEvent;
use App\Service\CombatLog\ResultEvents\CombatLogVersion as CombatLogVersionResultEvent;
use App\Service\CombatLog\ResultEvents\MapChange as MapChangeResultEvent;
use App\Service\CombatLog\ResultEvents\ZoneChange as ZoneChangeResultEvent;
use Exception;
use Illuminate\Support\Collection;

abstract class BaseSpecialEventsFilter implements CombatLogParserInterface
{
    /**
     * @param Collection<int, BaseResultEvent> $resultEvents
     */
    public function __construct(protected Collection $resultEvents)
    {
    }

    /**
     * @throws FloorNotSupportedException
     * @throws DungeonNotSupportedException
     * @throws Exception
     */
    public function parse(BaseEvent $combatLogEvent, int $lineNr): bool
    {
        // Combat log versions yes please
        if ($combatLogEvent instanceof CombatLogVersion) {
            $this->resultEvents->push(new CombatLogVersionResultEvent($combatLogEvent));

            return true;
        }

        // Combatant Info.. yes!
        if ($combatLogEvent instanceof CombatantInfoInterface) {
            $this->resultEvents->push(new CombatantInfoResultEvent($combatLogEvent));

            return true;
        }

        // Zone changes yes please
        if ($combatLogEvent instanceof ZoneChange) {
            try {
                $this->resultEvents->push(new ZoneChangeResultEvent($combatLogEvent));
            } catch (DungeonNotSupportedException) {
                // It's okay - we can ignore this
            }

            return true;
        }

        // Map changes yes please
        if ($combatLogEvent instanceof MapChange) {
            try {
                $this->resultEvents->push(new MapChangeResultEvent($combatLogEvent));
            } catch (FloorNotSupportedException) {
                // It's okay - we can ignore this
            }

            return true;
        }

        return false;
    }
}
