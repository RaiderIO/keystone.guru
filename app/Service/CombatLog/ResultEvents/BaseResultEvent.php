<?php

namespace App\Service\CombatLog\ResultEvents;

use App\Logic\CombatLog\BaseEvent;

abstract class BaseResultEvent
{
    private BaseEvent $baseEvent;

    /**
     * @param BaseEvent $baseEvent
     */
    public function __construct(BaseEvent $baseEvent)
    {
        $this->baseEvent = $baseEvent;
    }

    /**
     * @return BaseEvent
     */
    public function getBaseEvent(): BaseEvent
    {
        return $this->baseEvent;
    }
}
