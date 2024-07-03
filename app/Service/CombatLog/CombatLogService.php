<?php

namespace App\Service\CombatLog;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\CombatLogVersion;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeStart as ChallengeModeStartEvent;
use App\Logic\CombatLog\SpecialEvents\CombatLogVersion as CombatLogVersionEvent;
use App\Logic\CombatLog\SpecialEvents\MapChange as MapChangeEvent;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Service\CombatLog\Dtos\ChallengeMode;
use App\Service\CombatLog\Exceptions\DungeonNotSupportedException;
use App\Service\CombatLog\Filters\DungeonRoute\CombatLogDungeonRouteFilter;
use App\Service\CombatLog\Filters\DungeonRoute\DungeonRouteFilter;
use App\Service\CombatLog\Filters\MappingVersion\CombatLogDungeonOrRaidFilter;
use App\Service\CombatLog\Logging\CombatLogServiceLoggingInterface;
use App\Service\Season\SeasonServiceInterface;
use Exception;
use File;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use ZipArchive;

class CombatLogService implements CombatLogServiceInterface
{
    public function __construct(private readonly SeasonServiceInterface $seasonService, private readonly CombatLogServiceLoggingInterface $log)
    {
    }

    /**
     * @return Collection|BaseEvent[]
     *
     * @throws Exception
     */
    public function parseCombatLogToEvents(string $filePath): Collection
    {
        $events = new Collection();

        $this->parseCombatLog($filePath, function (int $combatLogVersion, string $rawEvent) use ($events) {
            $parsedEvent = (new CombatLogEntry($rawEvent))->parseEvent([], $combatLogVersion);

            if ($parsedEvent !== null) {
                $events->push($parsedEvent);
            } else {
                $this->log->parseCombatLogToEventsUnableToParseRawEvent($rawEvent);
            }

            return $parsedEvent;
        });

        return $events;
    }

    /**
     * @throws Exception
     */
    public function parseCombatLogStreaming(string $filePath, callable $callable): void
    {
        $this->parseCombatLog($filePath, function (int $combatLogVersion, string $rawEvent, int $lineNr) use ($callable) {
            $parsedEvent = (new CombatLogEntry($rawEvent))->parseEvent([], $combatLogVersion);

            if ($parsedEvent !== null) {
                $callable($parsedEvent, $lineNr);
            } else {
                $this->log->parseCombatLogToEventsUnableToParseRawEvent($rawEvent);
            }

            return $parsedEvent;
        });
    }

    /**
     * @return Collection|ChallengeMode
     *
     * @throws Exception
     */
    public function getChallengeModes(string $filePath): Collection
    {
        $events = new Collection();

        $this->parseCombatLog($filePath, static function (int $combatLogVersion, string $rawEvent) use ($events) {
            $parsedEvent = (new CombatLogEntry($rawEvent))->parseEvent(
                [SpecialEvent::SPECIAL_EVENT_CHALLENGE_MODE_START], $combatLogVersion
            );
            if ($parsedEvent instanceof ChallengeModeStartEvent) {
                try {
                    $dungeon = Dungeon::where('challenge_mode_id', $parsedEvent->getChallengeModeId())->firstOrFail();
                } catch (Exception) {
                    throw new DungeonNotSupportedException(
                        sprintf('Dungeon with challenge mode ID %d not found', $parsedEvent->getChallengeModeId())
                    );
                }

                $events->push((new ChallengeMode(
                    $parsedEvent->getTimestamp(),
                    $dungeon,
                    $parsedEvent->getKeystoneLevel()
                )));
            }

            return $parsedEvent;
        });

        return $events;
    }

    /**
     * @return Collection|ChallengeMode
     *
     * @throws Exception
     */
    public function getUiMapIds(string $filePath): Collection
    {
        $result = new Collection();

        $this->parseCombatLog($filePath, static function (int $combatLogVersion, string $rawEvent) use ($result) {
            $parsedEvent = (new CombatLogEntry($rawEvent))->parseEvent([SpecialEvent::SPECIAL_EVENT_MAP_CHANGE], $combatLogVersion);
            if ($parsedEvent instanceof MapChangeEvent) {
                $result->put($parsedEvent->getUiMapID(), $parsedEvent->getUiMapName());
            }

            return $parsedEvent;
        });

        return $result;
    }

    /**
     * @throws Exception
     */
    public function getResultEventsForChallengeMode(
        string        $combatLogFilePath,
        ?DungeonRoute &$dungeonRoute = null
    ): Collection {
        try {
            $this->log->getResultEventsStart($combatLogFilePath);
            $dungeonRouteFilter          = (new DungeonRouteFilter($this->seasonService));
            $combatLogDungeonRouteFilter = new CombatLogDungeonRouteFilter();

            $this->parseCombatLogStreaming($combatLogFilePath,
                static function (BaseEvent $baseEvent, int $lineNr) use (&$dungeonRouteFilter, &$combatLogDungeonRouteFilter) {
                    // If parsing was successful, it generated a dungeonroute, so then construct our filter
                    if ($dungeonRouteFilter->parse($baseEvent, $lineNr)) {
                        $combatLogDungeonRouteFilter->setDungeonRoute($dungeonRouteFilter->getDungeonRoute());
                    }
                    $combatLogDungeonRouteFilter->parse($baseEvent, $lineNr);
                }
            );

            // Output the dungeon route as well
            $dungeonRoute = $dungeonRouteFilter->getDungeonRoute();

            return $combatLogDungeonRouteFilter->getResultEvents();
        } finally {
            $this->log->getResultEventsEnd();
        }
    }

    /**
     * @throws Exception
     */
    public function getResultEventsForDungeonOrRaid(
        string $combatLogFilePath
    ): Collection {
        try {
            $this->log->getResultEventsStart($combatLogFilePath);
            $combatLogDungeonOrRaidFilter = new CombatLogDungeonOrRaidFilter();

            $this->parseCombatLogStreaming($combatLogFilePath,
                static function (BaseEvent $baseEvent, int $lineNr) use (&$combatLogDungeonOrRaidFilter) {
                    $combatLogDungeonOrRaidFilter->parse($baseEvent, $lineNr);
                }
            );

            return $combatLogDungeonOrRaidFilter->getResultEvents();
        } finally {
            $this->log->getResultEventsEnd();
        }
    }

    /**
     * @return string|null Null if the file was not a zip file and was not extracted
     */
    public function extractCombatLog(string $filePath): ?string
    {
        if (!Str::endsWith($filePath, '.zip')) {
            return null;
        }

        $this->log->extractCombatLogExtractingArchiveStart();
        $zip = new ZipArchive();
        try {
            $status = $zip->open($filePath);
            if ($status !== true) {
                $this->log->extractCombatLogInvalidZipFile();
                throw new InvalidArgumentException('File is not a valid .zip file');
            }

            $storageDestinationPath = '/tmp';
            if (!File::exists($storageDestinationPath)) {
                File::makeDirectory($storageDestinationPath, 0755, true);
            }

            $zip->extractTo($storageDestinationPath);

            $extractedFilePath = sprintf('%s/%s.txt', $storageDestinationPath, basename($filePath, '.zip'));
            $this->log->extractCombatLogExtractedArchive($extractedFilePath);
        } finally {
            $zip->close();
            $this->log->extractCombatLogExtractingArchiveEnd();
        }

        return $extractedFilePath;
    }

    public function compressCombatLog(string $filePathToTxt): string
    {
        if (Str::endsWith($filePathToTxt, '.zip')) {
            return $filePathToTxt;
        }

        $targetFilePath = sprintf(
            '%s/%s.zip',
            dirname($filePathToTxt),
            pathinfo($filePathToTxt, PATHINFO_FILENAME)
        );

        $this->log->compressCombatLogCompressingArchiveStart();
        $zip = new ZipArchive();
        try {
            $status = $zip->open($targetFilePath, ZipArchive::CREATE);
            if ($status !== true) {
                $this->log->compressCombatLogInvalidZipFile();
                throw new InvalidArgumentException('Could not create new .zip file');
            }

            $zip->addFile($filePathToTxt, basename($filePathToTxt));

            $this->log->compressCombatLogCompressedArchive($targetFilePath);
        } finally {
            $zip->close();
            $this->log->compressCombatLogCompressingArchiveEnd();
        }

        return $targetFilePath;
    }

    /**
     * @throws Exception
     */
    public function parseCombatLog(string $filePath, callable $callback): void
    {
        // Extracts the file if necessary
        $extractedFilePath = $this->extractCombatLog($filePath);

        $targetFilePath = $extractedFilePath ?? $filePath;

        $handle = fopen($targetFilePath, 'r');
        if (!$handle) {
            throw new InvalidArgumentException(sprintf('Unable to read file %s', $targetFilePath));
        }

        $lineNr   = 0;
        $rawEvent = '';
        try {
            $this->log->parseCombatLogParseEventsStart();
            $combatLogVersion = CombatLogVersion::RETAIL;
            while (($rawEvent = fgets($handle)) !== false) {
                $parsedEvent = $callback($combatLogVersion, $rawEvent, ++$lineNr);
                if ($parsedEvent instanceof CombatLogVersionEvent) {
                    $combatLogVersion = $parsedEvent->getVersion();
                    $this->log->parseCombatLogParseEventsChangedCombatLogVersion($combatLogVersion);
                }
            }
        } catch (Exception $exception) {
            throw new Exception(sprintf('%d: %s', $lineNr, $rawEvent), $exception->getCode(), $exception);
        } finally {
            $this->log->parseCombatLogParseEventsEnd();

            fclose($handle);

            if (file_exists($extractedFilePath)) {
                unlink($extractedFilePath);
            }
        }
    }

    public function saveCombatLogToFile(Collection $rawEvents, string $filePath): bool
    {
        $fileHandle = fopen($filePath, 'w');
        if ($fileHandle === false) {
            return false;
        }

        foreach ($rawEvents as $rawEvent) {
            fwrite($fileHandle, (string)$rawEvent);
        }

        return true;
    }
}
