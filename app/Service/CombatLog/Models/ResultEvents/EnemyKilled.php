<?php

namespace App\Service\CombatLog\Models\ResultEvents;

use App\Logic\CombatLog\SpecialEvents\UnitDied;

class EnemyKilled extends BaseResultEvent
{
    /**
     * @return UnitDied
     */
    public function getUnitDiedEvent(): UnitDied
    {
        /** @var UnitDied $unitDiedEvent */
        $unitDiedEvent = $this->getBaseEvent();

        return $unitDiedEvent;
    }
}
