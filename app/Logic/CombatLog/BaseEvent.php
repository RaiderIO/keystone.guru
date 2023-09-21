<?php

namespace App\Logic\CombatLog;

use Carbon\Carbon;

abstract class BaseEvent
{
    private int $combatLogVersion;

    private Carbon $timestamp;

    private string $eventName;

    private string $rawEvent;

    /**
     * @param int    $combatLogVersion
     * @param Carbon $timestamp
     * @param string $eventName
     * @param string $rawEvent
     */
    public function __construct(int $combatLogVersion, Carbon $timestamp, string $eventName, string $rawEvent)
    {
        $this->combatLogVersion = $combatLogVersion;
        $this->timestamp        = $timestamp;
        $this->eventName        = $eventName;
        $this->rawEvent         = $rawEvent;
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
