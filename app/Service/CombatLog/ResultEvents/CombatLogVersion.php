<?php

namespace App\Service\CombatLog\ResultEvents;

use App\Logic\CombatLog\SpecialEvents\CombatLogVersion as CombatLogVersionEvent;

class CombatLogVersion extends BaseResultEvent
{
    public function __construct(CombatLogVersionEvent $baseEvent)
    {
        parent::__construct($baseEvent);
    }
}
