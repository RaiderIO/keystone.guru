<?php

namespace App\Logic\CombatLog;

use Carbon\Carbon;

abstract class BaseEvent
{
    private Carbon $timestamp;

    private string $eventName;

    /**
     * @param Carbon $timestamp
     * @param string $eventName
     */
    public function __construct(Carbon $timestamp, string $eventName)
    {
        $this->timestamp = $timestamp;
        $this->eventName = $eventName;
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
}
