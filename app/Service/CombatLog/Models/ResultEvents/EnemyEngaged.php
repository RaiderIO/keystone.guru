<?php

namespace App\Service\CombatLog\Models\ResultEvents;

use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\Guid\Creature;

/**
 * @package App\Service\CombatLog\Models\ResultEvents
 * @author Wouter
 * @since 01/06/2023
 */
class EnemyEngaged extends BaseResultEvent
{
    private Creature $guid;

    /**
     * @param AdvancedCombatLogEvent $baseEvent
     * @param Creature $guid
     */
    public function __construct(AdvancedCombatLogEvent $baseEvent, Creature $guid)
    {
        parent::__construct($baseEvent);

        $this->guid = $guid;
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
        return $this->guid;
    }
}
