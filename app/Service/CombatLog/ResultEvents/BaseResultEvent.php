<?php

namespace App\Service\CombatLog\ResultEvents;

use App\Logic\CombatLog\BaseEvent;

abstract class BaseResultEvent
{
    public function __construct(private readonly BaseEvent $baseEvent)
    {
    }

    public function getBaseEvent(): BaseEvent
    {
        return $this->baseEvent;
    }
}
