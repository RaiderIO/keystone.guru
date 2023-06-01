<?php

namespace App\Service\CombatLog;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeStart;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;
use App\Models\Dungeon;
use App\Service\CombatLog\Logging\CombatLogServiceLoggingInterface;
use App\Service\CombatLog\Models\ChallengeMode;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
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
     *
     * @return Collection|BaseEvent[]
     */
    public function parseCombatLogToEvents(string $filePath): Collection
    {
        $events = new Collection();

        $this->parseCombatLog($filePath, function (string $rawEvent) use ($events)
        {
            $parsedEvent = (new CombatLogEntry($rawEvent))->parseEvent();

            if ($parsedEvent !== null) {
                $events->push($parsedEvent);
            } else {
                $this->log->parseCombatLogToEventsUnableToParseRawEvent($rawEvent);
            }
        });

        return $events;
    }
    /**
     * @param string $filePath
     *
     * @return Collection|ChallengeMode
     */
    public function getChallengeModes(string $filePath): Collection
    {
        $events = new Collection();

        $this->parseCombatLog($filePath, function (string $rawEvent) use ($events)
        {
            $parsedEvent = (new CombatLogEntry($rawEvent))->parseEvent([SpecialEvent::SPECIAL_EVENT_CHALLENGE_MODE_START]);

            if ($parsedEvent instanceof ChallengeModeStart) {
                $events->push((new ChallengeMode(
                    $parsedEvent->getTimestamp(),
                    Dungeon::where('map_id', $parsedEvent->getInstanceID())->firstOrFail(),
                    $parsedEvent->getKeystoneLevel()
                )));
            }
        });

        return $events;
    }

    /**
     * @param string   $filePath
     * @param callable $callback
     *
     * @return void
     */
    private function parseCombatLog(string $filePath, callable $callback): void
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new InvalidArgumentException(sprintf('Unable to read file %s', $filePath));
        }

        $events = new Collection();
        try {
            while (($rawEvent = fgets($handle)) !== false) {
                $callback($rawEvent);
            }
        }
        finally {
            fclose($handle);
        }
    }
}
