<?php

namespace App\Logic\CombatLog;

abstract class BaseEvent
{
    private string $eventName;

    /**
     * @param string $eventName
     */
    public function __construct(string $eventName)
    {
        $this->eventName = $eventName;
    }

    /**
     * @return string
     */
    public function getEventName(): string
    {
        return $this->eventName;
    }

    /**
     * @param array $parameters
     * @return self
     */
    abstract public function setParameters(array $parameters): self;
}
