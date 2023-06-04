<?php

namespace App\Logic\CombatLog;

use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\CombatEvents\CombatLogEvent;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CombatLogEntry
{
    private const RAW_EVENT_IGNORE = [
        'Search the gold piles for magic items!',
        'Everyone within 10 yards will be consumed!',
    ];

    private string $rawEvent;

    private Carbon $parsedTimestamp;

    private ?BaseEvent $parsedEvent = null;

    /**
     * @param string $rawEvent
     */
    public function __construct(string $rawEvent)
    {
        $this->rawEvent = $rawEvent;
    }

    /**
     * @param array $eventWhiteList Empty to return all events
     *
     * @return BaseEvent|null
     */
    public function parseEvent(array $eventWhiteList = []): ?BaseEvent
    {
        $matches = [];
        if (!preg_match('/(.+)\s\s(.+)/', $this->rawEvent, $matches)) {
            if (!in_array(trim($this->rawEvent), self::RAW_EVENT_IGNORE)) {
                throw new InvalidArgumentException(sprintf('Unable to parse event %s', $this->rawEvent));
            }

            return null;
        }

        $this->parsedTimestamp = Carbon::createFromFormat('m/d H:i:s.v', $matches[1]);
        $eventData             = $matches[2];
        $mayParseEvent         = empty($eventWhiteList);

        if (!$mayParseEvent) {
            foreach ($eventWhiteList as $whiteListedName) {
                if ($mayParseEvent = Str::startsWith($eventData, $whiteListedName)) {
                    break;
                }
            }
        }

        if ($mayParseEvent) {
            $parameters = str_getcsv($eventData);

            $eventName = array_shift($parameters);

            try {
                if (in_array($eventName, SpecialEvent::SPECIAL_EVENT_ALL)) {
                    $this->parsedEvent = SpecialEvent::createFromEventName($this->parsedTimestamp, $eventName, $parameters);
                }
                // https://wowpedia.fandom.com/wiki/COMBAT_LOG_EVENT
                // 11 base, 3 prefix, 9 suffix = 23 max parameters for non-advanced
                else if (count($parameters) > 23) {
                    $this->parsedEvent = (new AdvancedCombatLogEvent($this->parsedTimestamp, $eventName))->setParameters($parameters);
                } else {
                    $this->parsedEvent = (new CombatLogEvent($this->parsedTimestamp, $eventName))->setParameters($parameters);
                }

            } catch (\Error|Exception $exception) {
                echo sprintf('%s parsing: %s', PHP_EOL . PHP_EOL . $exception->getMessage(), $this->rawEvent);

                throw $exception;
            }
        }

        return $this->parsedEvent;
    }

    /**
     * @return string
     */
    public function getRawEvent(): string
    {
        return $this->rawEvent;
    }

    /**
     * @return Carbon
     */
    public function getParsedTimestamp(): Carbon
    {
        return $this->parsedTimestamp;
    }

    /**
     * @return BaseEvent|null
     */
    public function getParsedEvent(): ?BaseEvent
    {
        return $this->parsedEvent;
    }
}
