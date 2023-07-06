<?php

namespace App\Service\CombatLog\ResultEvents;

use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\Guid\Creature;
use App\Logic\CombatLog\SpecialEvents\GenericSpecialEvent;

class EnemyKilled extends BaseResultEvent
{
    /**
     * @return Creature
     */
    public function getGuid(): Creature
    {
        /** @var GenericSpecialEvent|AdvancedCombatLogEvent $baseEvent */
        $baseEvent = $this->getBaseEvent();

        /** @var Creature $guid */
        $guid = $baseEvent->getGenericData()->getDestGuid();

        return $guid;
    }
}
