<?php

namespace App\Service\CombatLog\ResultEvents;

use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\Guid\Creature;

/**
 * @package App\Service\CombatLog\Models\ResultEvents
 * @author Wouter
 * @since 01/06/2023
 */
class EnemyEngaged extends BaseResultEvent
{
    /**
     * @param AdvancedCombatLogEvent $baseEvent
     */
    public function __construct(AdvancedCombatLogEvent $baseEvent)
    {
        parent::__construct($baseEvent);
    }

    /**
     * @return AdvancedCombatLogEvent
     */
    public function getEngagedEvent(): AdvancedCombatLogEvent
    {
        /** @var AdvancedCombatLogEvent $baseEvent */
        $baseEvent = $this->getBaseEvent();

        return $baseEvent;
    }

    /**
     * @return Creature
     */
    public function getGuid(): Creature
    {
        /** @var Creature $guid */
        $guid = $this->getEngagedEvent()->getAdvancedData()->getInfoGuid();
        
        return $guid;
    }
}
