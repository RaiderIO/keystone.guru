<?php

namespace App\Logic\CombatLog;


use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\CombatEvents\CombatLogEvent;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;
use Carbon\Carbon;
use InvalidArgumentException;

class CombatLogEntry
{
    private Carbon $timestamp;

    private string $rawEvent;

    private ?BaseEvent $parsedEvent;

    /**
     * @param string $rawEvent
     */
    public function __construct(string $rawEvent)
    {
        $this->rawEvent = $rawEvent;
    }

    /**
     * @return BaseEvent|null
     */
    public function parseEvent(): ?BaseEvent
    {
        $matches = [];
        if (!preg_match('/(.+)\s\s(.+)/', $this->rawEvent, $matches)) {
            throw new InvalidArgumentException(sprintf('Unable to parse event %s', $this->rawEvent));
        }

        $this->timestamp = Carbon::createFromFormat('m/d H:i:s.v', $matches[1]);
        $eventData       = $matches[2];
        $parameters      = str_getcsv($eventData);

        $eventName = array_shift($parameters);

        if (in_array($eventName, SpecialEvent::SPECIAL_EVENT_ALL)) {
            $this->parsedEvent = SpecialEvent::createFromEventName($eventName, $parameters);
        }
        // https://wowpedia.fandom.com/wiki/COMBAT_LOG_EVENT
        // 11 base, 3 prefix, 9 suffix = 23 max parameters for non-advanced
        else if (count($parameters) > 23) {
            $this->parsedEvent = (new AdvancedCombatLogEvent($eventName))->setParameters($parameters);
        } else {
            $this->parsedEvent = (new CombatLogEvent($eventName))->setParameters($parameters);
        }

        return $this->parsedEvent;
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
    public function getRawEvent(): string
    {
        return $this->rawEvent;
    }

    /**
     * @return BaseEvent|null
     */
    public function getParsedEvent(): ?BaseEvent
    {
        return $this->parsedEvent;
    }
}
