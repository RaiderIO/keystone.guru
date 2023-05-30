<?php

namespace App\Service\CombatLog\Models\ResultEvents;

use App\Logic\CombatLog\BaseEvent;

class EnemyKilled extends BaseResultEvent
{
    /**
     * @return BaseEvent
     */
    public function getUnitDiedEvent(): BaseEvent
    {
        return $this->getBaseEvent();
    }
}
