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
     * @throws \Exception
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
     * @throws \Exception
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
     * @param string $filePath
     *
     * @return string|null Null if the file was not a zip file and was not extracted
     */
    private function extractCombatLog(string $filePath): ?string
    {
        if (!Str::endsWith($filePath, '.zip')) {
            return null;
        }

        $this->log->extractCombatLogExtractingArchive();
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
        }
        finally {
            $zip->close();
        }

        return $extractedFilePath;
    }

    /**
     * @param string   $filePath
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
}
