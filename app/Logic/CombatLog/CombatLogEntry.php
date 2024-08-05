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
    public const DATE_FORMAT = 'm/d/Y H:i:s.v-4';

    private const RAW_EVENT_IGNORE = [
        'Search the gold piles for magic items!',
        'Everyone within 10 yards will be consumed!',
        // This is sometimes randomly put in the combat log. If this is not a bug and something that keeps returning
        // we may need to find a better solution
        'COMBAT_LOG_VERSION,20,ADVANCED_LOG_ENABLED,1,BUILD_VERSION,11.0.0,PROJECT_ID,1'
    ];

    private ?Carbon $parsedTimestamp = null;

    private ?BaseEvent $parsedEvent = null;

    public function __construct(private readonly string $rawEvent)
    {
    }

    /**
     * @param array $eventWhiteList Empty to return all events
     *
     * @throws Exception
     */
    public function parseEvent(array $eventWhiteList = [], int $combatLogVersion = CombatLogVersion::RETAIL): ?BaseEvent
    {
        $matches = [];
        if (!preg_match('/(\d*\/\d*(?:\/\d*)? \d*:\d*:\d*.\d*(?:-\d*)?)\s\s(.+)/', $this->rawEvent, $matches)) {
            if (!in_array(trim($this->rawEvent), self::RAW_EVENT_IGNORE)) {
                throw new InvalidArgumentException(sprintf('Unable to parse event %s', $this->rawEvent));
            }

            return null;
        }

        try {
            $this->parsedTimestamp = Carbon::createFromFormat(self::DATE_FORMAT, $matches[1]);
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
}
