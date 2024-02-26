<?php

namespace App\Logic\CombatLog;

use Carbon\Carbon;

abstract class BaseEvent
{
    public function __construct(private readonly int $combatLogVersion, private readonly Carbon $timestamp, private readonly string $eventName, private readonly string $rawEvent)
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
