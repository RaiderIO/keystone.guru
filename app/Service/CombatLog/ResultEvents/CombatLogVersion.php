<?php

namespace App\Service\CombatLog\ResultEvents;

use App\Logic\CombatLog\SpecialEvents\CombatLogVersion as CombatLogVersionEvent;
use App\Logic\CombatLog\CombatLogVersion as CombatLogVersionConstant;

class CombatLogVersion extends BaseResultEvent
{
    public function __construct(CombatLogVersionEvent $baseEvent)
    {
        parent::__construct($baseEvent);

        if (!isset(CombatLogVersionConstant::ALL[$baseEvent->getVersion()])) {
            throw new \Exception(sprintf('Unable to find combat log version %d!', $baseEvent->getVersion()));
        }
    }
}
