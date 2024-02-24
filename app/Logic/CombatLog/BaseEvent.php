<?php

namespace App\Logic\CombatLog;

use Carbon\Carbon;

abstract class BaseEvent
{
    public function __construct(private int $combatLogVersion, private Carbon $timestamp, private string $eventName, private string $rawEvent)
    {
    }

    /**
     * @return int
     */
    public function getCombatLogVersion(): int
    {
        return $this->combatLogVersion;
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
