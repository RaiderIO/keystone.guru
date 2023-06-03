<?php

namespace App\Service\CombatLog;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeEnd as ChallengeModeEndEvent;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeStart as ChallengeModeStartEvent;
use App\Logic\CombatLog\SpecialEvents\CombatLogVersion as CombatLogVersionEvent;
use App\Logic\CombatLog\SpecialEvents\MapChange as MapChangeEvent;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;
use App\Logic\CombatLog\SpecialEvents\ZoneChange as ZoneChangeEvent;
use App\Models\Dungeon;
use App\Service\CombatLog\Logging\CombatLogServiceLoggingInterface;
use App\Service\CombatLog\Models\ChallengeMode;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use ZipArchive;

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
     * @throws \Exception
     */
    public function parseCombatLogToEvents(string $filePath): Collection
    {
        $events = new Collection();

        $this->parseCombatLog($filePath, function (string $rawEvent) use ($events) {
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
     * @throws \Exception
     */
    public function getChallengeModes(string $filePath): Collection
    {
        $events = new Collection();

        $this->parseCombatLog($filePath, function (string $rawEvent) use ($events) {
            $parsedEvent = (new CombatLogEntry($rawEvent))->parseEvent([SpecialEvent::SPECIAL_EVENT_CHALLENGE_MODE_START]);

            if ($parsedEvent instanceof ChallengeModeStartEvent) {
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
     * @param string $filePath
     * @return Collection
     * @throws \Exception
     */
    public function splitCombatLogOnChallengeModes(string $filePath): Collection
    {
        $filePath  = $this->extractCombatLog($filePath) ?? $filePath;
        $rawEvents = collect();
        $result    = collect();

        // We don't need to do anything if there was just one run
        if ($this->getChallengeModes($filePath)->count() <= 1) {
            return $result->push($filePath);
        }

        // The events we want to keep OUTSIDE OF challenge modes
        $eventsToKeep = [
            SpecialEvent::SPECIAL_EVENT_COMBAT_LOG_VERSION,
            SpecialEvent::SPECIAL_EVENT_ZONE_CHANGE,
            SpecialEvent::SPECIAL_EVENT_MAP_CHANGE,
            SpecialEvent::SPECIAL_EVENT_CHALLENGE_MODE_START,
            SpecialEvent::SPECIAL_EVENT_CHALLENGE_MODE_END,
        ];

        // Keep track of the most recent occurrences of these events
        $lastCombatLogVersion        = null;
        $lastChallengeModeStartEvent = null;
        $lastZoneChange              = null;
        $lastMapChange               = null;

        $this->parseCombatLog($filePath, function (string $rawEvent)
        use (
            $filePath, &$result, &$rawEvents, &$eventsToKeep,
            &$lastCombatLogVersion, &$lastChallengeModeStartEvent, &$lastZoneChange, &$lastMapChange
        ) {
            $parsedEvent = (new CombatLogEntry($rawEvent))->parseEvent($eventsToKeep);

            // If we have started a challenge mode
            if ($lastChallengeModeStartEvent instanceof ChallengeModeStartEvent) {
                // Save ALL events that come through after the challenge mode start event has been given
                $rawEvents->push($rawEvent);

                // And it's ended
                if ($parsedEvent instanceof ChallengeModeEndEvent) {
                    $targetFilePath = sprintf('%s/%s_%d_%s.txt',
                        dirname($filePath),
                        pathinfo($filePath, PATHINFO_FILENAME),
                        $lastChallengeModeStartEvent->getKeystoneLevel(),
                        Str::slug($lastChallengeModeStartEvent->getZoneName())
                    );

                    $this->saveCombatLogToFile($rawEvents, $targetFilePath);

                    // Add the .txt to a .zip
                    $compressedTargetFilePath = $this->compressCombatLog($targetFilePath);
                    $result->push($compressedTargetFilePath);

                    // remove the .txt
                    unlink($targetFilePath);

                    // Reset variables
                    $lastChallengeModeStartEvent = null;
                    $rawEvents                   = collect();
                }
            } else if ($parsedEvent instanceof ChallengeModeStartEvent) {
                $lastChallengeModeStartEvent = $parsedEvent;

                $rawEvents->push($lastCombatLogVersion);
                $rawEvents->push($lastZoneChange);
                $rawEvents->push($lastMapChange);
                $rawEvents->push($rawEvent);
            } else if ($parsedEvent instanceof CombatLogVersionEvent) {
                $lastCombatLogVersion = $rawEvent;
            } else if ($parsedEvent instanceof ZoneChangeEvent) {
                $lastZoneChange = $rawEvent;
            } else if ($parsedEvent instanceof MapChangeEvent) {
                $lastMapChange = $rawEvent;
            }
        });

        return $result;
    }


    /**
     * @param string $filePath
     *
     * @return string|null Null if the file was not a zip file and was not extracted
     */
    private function extractCombatLog(string $filePath): ?string
    {
        if (!Str::endsWith($filePath, '.zip')) {
            return null;
        }

        $this->log->extractCombatLogExtractingArchiveStart();
        $zip = new \ZipArchive();
        try {
            $status = $zip->open($filePath);
            if ($status !== true) {
                $this->log->extractCombatLogInvalidZipFile();
                throw new InvalidArgumentException('File is not a valid .zip file');
            }

            $storageDestinationPath = '/tmp';
            if (!\File::exists($storageDestinationPath)) {
                \File::makeDirectory($storageDestinationPath, 0755, true);
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

    /**
     * @param string $filePathToTxt
     * @return string
     */
    private function compressCombatLog(string $filePathToTxt): string
    {
        if (Str::endsWith($filePathToTxt, '.zip')) {
            return $filePathToTxt;
        }

        $targetFilePath = sprintf('%s/%s.zip',
            dirname($filePathToTxt),
            pathinfo($filePathToTxt, PATHINFO_FILENAME)
        );

        $this->log->compressCombatLogCompressingArchiveStart();
        $zip = new \ZipArchive();
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
     * @param string $filePath
     * @param callable $callback
     *
     * @return void
     * @throws \Exception
     */
    private function parseCombatLog(string $filePath, callable $callback): void
    {
        // Extracts the file if necessary
        $extractedFilePath = $this->extractCombatLog($filePath);

        $targetFilePath = $extractedFilePath ?? $filePath;

        $handle = fopen($targetFilePath, 'r');
        if (!$handle) {
            throw new InvalidArgumentException(sprintf('Unable to read file %s', $targetFilePath));
        }

        try {
            $this->log->parseCombatLogParseEventsStart();
            while (($rawEvent = fgets($handle)) !== false) {
                $callback($rawEvent);
            }
        } finally {
            $this->log->parseCombatLogParseEventsEnd();

            fclose($handle);

            if (file_exists($extractedFilePath)) {
                unlink($extractedFilePath);
            }
        }
    }

    /**
     * @param Collection $rawEvents
     * @param string $filePath
     * @return bool
     */
    private function saveCombatLogToFile(Collection $rawEvents, string $filePath): bool
    {
        $fileHandle = fopen($filePath, 'w');
        if ($fileHandle === false) {
            return false;
        }

        foreach ($rawEvents as $rawEvent) {
            fwrite($fileHandle, $rawEvent);
        }

        return true;
    }
}
