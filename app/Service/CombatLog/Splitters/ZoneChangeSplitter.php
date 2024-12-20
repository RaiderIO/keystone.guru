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
        // Nerub-ar Palace
        $this->validDungeonMapIds->put(2657, 2657);
    }

    public function splitCombatLog(string $filePath): Collection
    {
        $this->reset();

        $this->filePath = $filePath;

        // Pass $this->>parseCombatLogEvent as callable
        $this->combatLogService->parseCombatLog(
            $filePath,
            fn($combatLogVersion, $advancedLoggingEnabled, $rawEvent, $lineNr) => $this->parseCombatLogEvent($combatLogVersion, $advancedLoggingEnabled, $rawEvent, $lineNr)
        );

        // Make sure that everything captured from last zone change and onwards is still saved to disk
        if ($this->lastZoneChangeEvent !== null) {
            $this->flushRawEventsToFile();
        }

        // Remove the lineNr context since we stopped parsing lines, don't let the last line linger in the context
        $this->log->removeContext('lineNr');

        return $this->result;
    }

    private function parseCombatLogEvent(int $combatLogVersion, bool $advancedLoggingEnabled, string $rawEvent, int $lineNr)
    {
        $this->log->addContext('lineNr', [
            'combatLogVersion'       => $combatLogVersion,
            'advancedLoggingEnabled' => $advancedLoggingEnabled,
            'rawEvent'               => trim($rawEvent),
            'lineNr'                 => $lineNr,
        ]);

        $combatLogEntry = (new CombatLogEntry($rawEvent));
        $parsedEvent    = $combatLogEntry->parseEvent(self::EVENTS_TO_KEEP, $combatLogVersion);

        if ($combatLogEntry->getParsedTimestamp() === null) {
            $this->log->parseCombatLogEventTimestampNotSet();

            return $parsedEvent;
        }

        // If we have started a challenge mode
        if ($this->lastZoneChangeEvent instanceof ZoneChangeEvent) {
            // Save ALL events that come through after the challenge mode start event has been given
            $this->rawEvents->push($rawEvent);
            $this->lastTimestamp = $combatLogEntry->getParsedTimestamp();
        }


        // And it's ended (we don't care for the valid dungeon zone IDs whitelist, if we switched, we switched)
        if ($parsedEvent instanceof ZoneChangeEvent) {
            // Wrap up an existing zone if we had one, and only when we actually change zones!
            $zoneChanged = $this->lastZoneChangeEvent instanceof ZoneChangeEvent && $this->lastZoneChangeEvent->getZoneId() !== $parsedEvent->getZoneId();
            if ($zoneChanged) {
                $this->flushRawEventsToFile();
            }

            // If we're going to start a new zone
            if ($this->validDungeonMapIds->has($parsedEvent->getZoneId()) && ($this->lastZoneChangeEvent === null || $zoneChanged)) {
                $this->log->parseCombatLogEventZoneChangeEvent();

                $this->lastZoneChangeEvent = $parsedEvent;

                $this->rawEvents->push($this->lastCombatLogVersion);
                $this->rawEvents->push($rawEvent);
            }
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

    private function flushRawEventsToFile(): void
    {
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

    protected function getCombatLogFileName(string $countStr): string
    {
        return sprintf('%s_%s%s',
            pathinfo($this->filePath, PATHINFO_FILENAME),
            Str::slug($this->lastZoneChangeEvent->getZoneName()),
            $countStr
        );
    }

}
