<?php

namespace App\Logic\CombatLog;

use Carbon\Carbon;

abstract class BaseEvent
{
    private Carbon $timestamp;

    private string $eventName;
    
    private string $rawEvent;

    /**
     * @param Carbon $timestamp
     * @param string $eventName
     * @param string $rawEvent
     */
    public function __construct(Carbon $timestamp, string $eventName, string $rawEvent)
    {
        $this->timestamp = $timestamp;
        $this->eventName = $eventName;
        $this->rawEvent = $rawEvent;
    }

    /**
     * @return Carbon
     */
    public function getTimestamp(): Carbon
    {
        return $this->timestamp;
    }

    /**
     * @return string
     */
    public function getEventName(): string
    {
        return $this->eventName;
    }
    
    /**
     * @return string
     */
    public function getRawEvent(): string
    {
        return $this->rawEvent;
    }
}
