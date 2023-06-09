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
use App\Service\CombatLog\Models\CombatLogChallengeModeSplitter;
use Carbon\Carbon;
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
     * @param string   $filePath
     * @param callable $callable
     *
     * @return void
     * @throws \Exception
     */
    public function parseCombatLogStreaming(string $filePath, callable $callable): void
    {
        $this->parseCombatLog($filePath, function (string $rawEvent, int $lineNr) use ($callable) {
            $parsedEvent = (new CombatLogEntry($rawEvent))->parseEvent();

            if ($parsedEvent !== null) {
                $callable($parsedEvent, $lineNr);
            } else {
                $this->log->parseCombatLogToEventsUnableToParseRawEvent($rawEvent);
            }
        });
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
     *
     * @return Collection|ChallengeMode
     * @throws \Exception
     */
    public function getUiMapIds(string $filePath): Collection
    {
        $result = new Collection();

        $this->parseCombatLog($filePath, function (string $rawEvent) use ($result) {
            $parsedEvent = (new CombatLogEntry($rawEvent))->parseEvent([SpecialEvent::SPECIAL_EVENT_MAP_CHANGE]);

            if ($parsedEvent instanceof MapChangeEvent) {
                $result->put($parsedEvent->getUiMapID(), $parsedEvent->getUiMapName());
            }
        });

        return $result;
    }


    /**
     * @param string $filePath
     *
     * @return string|null Null if the file was not a zip file and was not extracted
     */
    public function extractCombatLog(string $filePath): ?string
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
    public function compressCombatLog(string $filePathToTxt): string
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
    public function parseCombatLog(string $filePath, callable $callback): void
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
            $lineNr = 1;
            while (($rawEvent = fgets($handle)) !== false) {
                $callback($rawEvent, $lineNr++);
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
    public function saveCombatLogToFile(Collection $rawEvents, string $filePath): bool
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
