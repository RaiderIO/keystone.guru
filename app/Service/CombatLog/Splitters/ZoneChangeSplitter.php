<?php

namespace App\Service\CombatLog\Splitters;

use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\SpecialEvents\CombatLogVersion as CombatLogVersionEvent;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;
use App\Logic\CombatLog\SpecialEvents\ZoneChange as ZoneChangeEvent;
use App\Repositories\Interfaces\DungeonRepositoryInterface;
use App\Service\CombatLog\CombatLogServiceInterface;
use App\Service\CombatLog\Splitters\Logging\ZoneChangeSplitterLoggingInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class ZoneChangeSplitter extends CombatLogSplitter
{
    private const MAX_TIMESTAMP_GAP_SECONDS = 10 * 60;

    private const EVENTS_TO_KEEP = [
        SpecialEvent::SPECIAL_EVENT_COMBAT_LOG_VERSION,
        SpecialEvent::SPECIAL_EVENT_ZONE_CHANGE,
    ];

    private ZoneChangeSplitterLoggingInterface $log;

    private Collection $validDungeonMapIds;

    /** @var Collection<string> */
    private Collection $rawEvents;

    private ?string $lastCombatLogVersion;

    private ?ZoneChangeEvent $lastZoneChangeEvent;

    private ?Carbon $lastTimestamp = null;

    private ?Collection $result = null;

    private ?string $filePath;

    public function __construct(
        private readonly CombatLogServiceInterface  $combatLogService,
        private readonly DungeonRepositoryInterface $dungeonRepository
    ) {
        $log = App::make(ZoneChangeSplitterLoggingInterface::class);
        /** @var ZoneChangeSplitterLoggingInterface $log */
        $this->log = $log;

        parent::__construct($this->log);

        // Flip keys and values, and yes
        $this->validDungeonMapIds = $this->dungeonRepository->getAllMapIds()->mapWithKeys(function (int $mapId) {
            return [$mapId => $mapId];
        });
    }

    public function splitCombatLog(string $filePath): Collection
    {
        $this->reset();

        $this->filePath = $filePath;

        // Pass $this->>parseCombatLogEvent as callable
        $this->combatLogService->parseCombatLog(
            $filePath,
            fn($combatLogVersion, $rawEvent, $lineNr) => $this->parseCombatLogEvent($combatLogVersion, $rawEvent, $lineNr)
        );


        return $this->result;
    }

    private function parseCombatLogEvent(int $combatLogVersion, string $rawEvent, int $lineNr)
    {
        $this->log->addContext('lineNr', ['combatLogVersion' => $combatLogVersion, 'rawEvent' => $rawEvent, 'lineNr' => $lineNr]);

        $combatLogEntry = (new CombatLogEntry($rawEvent));
        $parsedEvent    = $combatLogEntry->parseEvent(self::EVENTS_TO_KEEP, $combatLogVersion);

        if ($combatLogEntry->getParsedTimestamp() === null) {
            $this->log->parseCombatLogEventTimestampNotSet();

            return $parsedEvent;
        }

        // If we have started a challenge mode
        if ($this->lastZoneChangeEvent instanceof ZoneChangeEvent) {
            // If there's too much of a gap between the last entry and the next one, just ditch the run
            if ($this->lastTimestamp instanceof Carbon &&
                ($seconds = $this->lastTimestamp->diffInSeconds($combatLogEntry->getParsedTimestamp())) > self::MAX_TIMESTAMP_GAP_SECONDS) {
                $this->log->parseCombatLogEventTooBigTimestampGap(
                    $seconds,
                    $this->lastTimestamp->toDateTimeString(),
                    $combatLogEntry->getParsedTimestamp()->toDateTimeString()
                );

                // Reset variables
                $this->resetCurrentZone();

                return $parsedEvent;
            }

            // Save ALL events that come through after the challenge mode start event has been given
            $this->rawEvents->push($rawEvent);
            $this->lastTimestamp = $combatLogEntry->getParsedTimestamp();

            // And it's ended (we don't care for the valid dungeon zone IDs whitelist, if we switched, we switched)
            if ($parsedEvent instanceof ZoneChangeEvent) {
                $saveFilePath = $this->generateTargetCombatLogFileName($this->filePath);

                try {
                    $this->combatLogService->saveCombatLogToFile($this->rawEvents, $saveFilePath);

                    // Add the .txt to a .zip
                    $compressedTargetFilePath = $this->combatLogService->compressCombatLog($saveFilePath);
                    $this->result->push($compressedTargetFilePath);

                    // Reset variables
                    $this->resetCurrentZone();
                } finally {
                    // remove the .txt
                    unlink($saveFilePath);
                }
            }
        } // If we're going to start a new zone
        else if ($parsedEvent instanceof ZoneChangeEvent &&
            $this->validDungeonMapIds->has($parsedEvent->getZoneId())) {
            $this->log->parseCombatLogEventZoneChangeEvent();

            $this->lastZoneChangeEvent = $parsedEvent;

            $this->rawEvents->push($this->lastCombatLogVersion);
            $this->rawEvents->push($rawEvent);
        }

        // Always keep track of these events
        if ($parsedEvent instanceof CombatLogVersionEvent) {
            $this->log->parseCombatLogEventCombatLogVersionEvent();
            $this->lastCombatLogVersion = $rawEvent;
        }

        return $parsedEvent;
    }


    private function resetCurrentZone(): void
    {
        $this->log->resetCurrentZone();

        $this->rawEvents           = collect();
        $this->lastZoneChangeEvent = null;
        $this->lastTimestamp       = null;
    }

    private function reset(): void
    {
        $this->log->reset();
        $this->resetCurrentZone();

        $this->lastCombatLogVersion = null;
        $this->result               = collect();
    }

    protected function getCombatLogFileName(string $countStr): string
    {
        return sprintf('%s_%s%s',
            pathinfo($this->filePath, PATHINFO_FILENAME),
            Str::slug($this->lastZoneChangeEvent->getZoneName()),
            $countStr
        );
    }

}
