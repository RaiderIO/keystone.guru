<?php

namespace App\Service\CombatLog;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatLogEntry;
use App\Service\CombatLog\Logging\CombatLogServiceLoggingInterface;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class CombatLogService implements CombatLogServiceInterface
{
    private CombatLogServiceLoggingInterface $log;

    /**
     * @param CombatLogServiceLoggingInterface $log
     */
    public function __construct(CombatLogServiceLoggingInterface $log)
    {
        $this->log = $log;
    }

    /**
     * @param string $filePath
     * @return Collection|BaseEvent[]
     */
    public function parseCombatLogToEvents(string $filePath): Collection
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new InvalidArgumentException(sprintf('Unable to read file %s', $filePath));
        }

        $events = new Collection();
        try {
            while (($rawEvent = fgets($handle)) !== false) {
                $parsedEvent = (new CombatLogEntry($rawEvent))->parseEvent();

                if ($parsedEvent !== null) {
                    $events->push($parsedEvent);
                } else {
                    $this->log->parseCombatLogToEventsUnableToParseRawEvent($rawEvent);
                }
            }
        } finally {
            fclose($handle);
        }

        return $events;
    }
}
