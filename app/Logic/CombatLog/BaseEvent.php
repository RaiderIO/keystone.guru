<?php

namespace App\Logic\CombatLog;

use Illuminate\Support\Carbon;

abstract class BaseEvent
{
    public function __construct(
        private readonly int    $combatLogVersion,
        private readonly Carbon $timestamp,
        private readonly string $eventName,
        private readonly string $rawEvent
    ) {
    }

    public function getCombatLogVersion(): int
    {
        return $this->combatLogVersion;
    }

    public function getTimestamp(): Carbon
    {
        return $this->timestamp;
    }

    public function getEventName(): string
    {
        return $this->eventName;
    }

    public function getRawEvent(): string
    {
        return $this->rawEvent;
    }
}
