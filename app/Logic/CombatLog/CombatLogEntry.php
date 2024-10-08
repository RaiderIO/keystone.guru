<?php

namespace App\Logic\CombatLog;

use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\CombatEvents\CombatLogEvent;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;
use Carbon\Exceptions\InvalidFormatException;
use Error;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CombatLogEntry
{
    public const DATE_FORMATS = [
        'm/d H:i:s.v',
        'm/d/Y H:i:s.v-1',
        'm/d/Y H:i:s.v-2',
        'm/d/Y H:i:s.v-3',
        'm/d/Y H:i:s.v-4', // I don't  know what the -4 stands for - assuming timezone? There's no thingy for it though
        'm/d/Y H:i:s.v-5',
        'm/d/Y H:i:s.v-6',
        'm/d/Y H:i:s.v-7',
        'm/d/Y H:i:s.v-8',
        'm/d/Y H:i:s.v-9',
        'm/d/Y H:i:s.v1', // I don't know what the 1-9 stands for
        'm/d/Y H:i:s.v2',
        'm/d/Y H:i:s.v3',
        'm/d/Y H:i:s.v4',
        'm/d/Y H:i:s.v5',
        'm/d/Y H:i:s.v6',
        'm/d/Y H:i:s.v7',
        'm/d/Y H:i:s.v8',
        'm/d/Y H:i:s.v9',
        'm/d/Y H:i:s.v0',
    ];

    private const RAW_EVENT_IGNORE = [
        'Search the gold piles for magic items!',
        'Everyone within 10 yards will be consumed!',
        // This is sometimes randomly put in the combat log. If this is not a bug and something that keeps returning
        // we may need to find a better solution
        'COMBAT_LOG_VERSION,20,ADVANCED_LOG_ENABLED,1,BUILD_VERSION,11.0.0,PROJECT_ID,1',
    ];

    private ?Carbon $parsedTimestamp = null;

    private ?BaseEvent $parsedEvent = null;

    /** @var int|null Remembers what date format was last found in the combat log and uses that for subsequent format parses first. */
    private ?int $previousDateFormat = null;

    public function __construct(private readonly string $rawEvent)
    {
    }

    /**
     * @param array $eventWhiteList Empty to return all events
     *
     * @throws Exception
     */
    public function parseEvent(array $eventWhiteList = [], int $combatLogVersion = CombatLogVersion::RETAIL_10_1_0): ?BaseEvent
    {
        $matches = [];
        if (!preg_match('/(\d*\/\d*(?:\/\d*)? \d*:\d*:\d*.\d*(?:-\d*)?)\s\s(.+)/', $this->rawEvent, $matches)) {
            if (!in_array(trim($this->rawEvent), self::RAW_EVENT_IGNORE)) {
                throw new InvalidArgumentException(sprintf('Unable to parse event %s', $this->rawEvent));
            }

            return null;
        }

        try {
            $this->parsedTimestamp = $this->parseTimestamp($matches[1]);
        } catch (InvalidFormatException $invalidFormatException) {
            throw new Exception(sprintf('Unable to parse datetime: %s', $matches[1]), $invalidFormatException->getCode(), $invalidFormatException);
        }

        $eventData     = $matches[2];
        $mayParseEvent = empty($eventWhiteList);

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
                    $this->parsedEvent = SpecialEvent::createFromEventName($combatLogVersion, $this->parsedTimestamp, $eventName, $parameters, $this->rawEvent);
                }
                // https://wowpedia.fandom.com/wiki/COMBAT_LOG_EVENT
                // 11 base, 3 prefix, 9 suffix = 23 max parameters for non-advanced
                else if (count($parameters) > 23) {
                    $this->parsedEvent = (new AdvancedCombatLogEvent($combatLogVersion, $this->parsedTimestamp, $eventName, $this->rawEvent))->setParameters($parameters);
                } else {
                    $this->parsedEvent = (new CombatLogEvent($combatLogVersion, $this->parsedTimestamp, $eventName, $this->rawEvent))->setParameters($parameters);
                }

            } catch (Error|Exception $exception) {
                echo sprintf('%s parsing: %s', PHP_EOL . PHP_EOL . $exception->getMessage(), $this->rawEvent);

                throw $exception;
            }
        }

        return $this->parsedEvent;
    }

    public function getRawEvent(): string
    {
        return $this->rawEvent;
    }

    /**
     * @return Carbon|null Can be null if accessed before event was parsed, or if event was part of RAW_EVENT_IGNORE
     *                     and it didn't have any timestamp as a result.
     */
    public function getParsedTimestamp(): ?Carbon
    {
        return $this->parsedTimestamp;
    }

    public function getParsedEvent(): ?BaseEvent
    {
        return $this->parsedEvent;
    }

    private function parseTimestamp(string $timestamp): Carbon
    {
        $parsedTimestamp = null;

        // If we had a previous date format, try to parse with that format first
        if ($this->previousDateFormat !== null) {
            try {
                return Carbon::createFromFormat(self::DATE_FORMATS[$this->previousDateFormat], $timestamp);
            } catch (InvalidFormatException $invalidFormatException) {
                // Ignore, we'll try the other formats
            }
        }

        foreach (self::DATE_FORMATS as $key => $dateFormat) {
            // Don't double-check if it's not needed
            if ($key === $this->previousDateFormat) {
                continue;
            }

            try {
                $parsedTimestamp = Carbon::createFromFormat($dateFormat, $timestamp);

                $this->previousDateFormat = $key;
            } catch (InvalidFormatException $invalidFormatException) {
                continue;
            }
        }

        if ($parsedTimestamp === null) {
            throw new InvalidArgumentException(sprintf('Unable to parse datetime: %s', $timestamp));
        }

        return $parsedTimestamp;
    }
}
