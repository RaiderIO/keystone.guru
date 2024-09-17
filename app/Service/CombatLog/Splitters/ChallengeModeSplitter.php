<?php

namespace App\Service\CombatLog\Splitters;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeEnd as ChallengeModeEndEvent;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeStart as ChallengeModeStartEvent;
use App\Logic\CombatLog\SpecialEvents\CombatLogVersion as CombatLogVersionEvent;
use App\Logic\CombatLog\SpecialEvents\MapChange as MapChangeEvent;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;
use App\Logic\CombatLog\SpecialEvents\ZoneChange as ZoneChangeEvent;
use App\Models\Dungeon;
use App\Service\CombatLog\CombatLogServiceInterface;
use App\Service\CombatLog\Splitters\Logging\ChallengeModeSplitterLoggingInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class ChallengeModeSplitter extends CombatLogSplitter
{
    private const MAX_TIMESTAMP_GAP_SECONDS = 10 * 60;

    private const EVENTS_TO_KEEP = [
        SpecialEvent::SPECIAL_EVENT_COMBAT_LOG_VERSION,
        SpecialEvent::SPECIAL_EVENT_ZONE_CHANGE,
        SpecialEvent::SPECIAL_EVENT_MAP_CHANGE,
        SpecialEvent::SPECIAL_EVENT_CHALLENGE_MODE_START,
        SpecialEvent::SPECIAL_EVENT_CHALLENGE_MODE_END,
    ];

    private ChallengeModeSplitterLoggingInterface $log;
    /** @var Collection<string> */
    private Collection               $rawEvents;
    private ?CombatLogVersionEvent   $lastCombatLogVersionEvent;
    private ?ChallengeModeStartEvent $lastChallengeModeStartEvent = null;
    private ?Dungeon                 $currentDungeon              = null;
    private ?ZoneChangeEvent         $lastZoneChangeEvent;
    private ?MapChangeEvent          $lastMapChangeEvent;
    private ?Carbon                  $lastTimestamp               = null;
    private ?Collection              $result                      = null;
    private ?string $filePath;
    private bool    $isInWrongZone = false;

    public function __construct(
        private readonly CombatLogServiceInterface $combatLogService
    ) {
        $log = App::make(ChallengeModeSplitterLoggingInterface::class);
        /** @var ChallengeModeSplitterLoggingInterface $log */
        $this->log = $log;

        parent::__construct($this->log);
    }

    public function splitCombatLog(string $filePath): Collection
    {
        $this->reset();

        // We don't need to do anything if there are no runs
        // If there's one run, we may still want to trim the fat of the log and keep just
        // the one challenge mode that's in there
        $foundChallengeModes = $this->combatLogService->getChallengeModes($filePath)->count();
        if ($foundChallengeModes <= 0) {
            $this->log->splitCombatLogNoChallengeModesFound();

            return $this->result;
        }


        $this->filePath = $filePath;

        // Pass $this->>parseCombatLogEvent as callable
        $this->combatLogService->parseCombatLog(
            $filePath,
            fn($combatLogVersion, $rawEvent, $lineNr) => $this->parseCombatLogEvent($combatLogVersion, $rawEvent, $lineNr)
        );

        // Remove the lineNr context since we stopped parsing lines, don't let the last line linger in the context
        $this->log->removeContext('lineNr');

        if ($this->lastChallengeModeStartEvent !== null) {
            $this->log->splitCombatLogLastRunNotCompleted();
        }

        if ($foundChallengeModes !== $this->result->count()) {
            // The number of challenge modes found does not match the number of challenge modes split from combat log,
            // did they not finish a run? Something else going on perhaps?
            $this->log->splitCombatLogChallengeModeAndResultMismatched();
        }

        return $this->result;
    }

    private function parseCombatLogEvent(int $combatLogVersion, string $rawEvent, int $lineNr): ?BaseEvent
    {
        $this->log->addContext('lineNr', ['combatLogVersion' => $combatLogVersion, 'rawEvent' => trim($rawEvent), 'lineNr' => $lineNr]);

        $combatLogEntry = (new CombatLogEntry($rawEvent));
        $parsedEvent    = $combatLogEntry->parseEvent(self::EVENTS_TO_KEEP, $combatLogVersion);

        if ($combatLogEntry->getParsedTimestamp() === null) {
            $this->log->parseCombatLogEventTimestampNotSet();

            return $parsedEvent;
        }

        // If we have started a challenge mode
        if ($this->lastChallengeModeStartEvent instanceof ChallengeModeStartEvent) {
            // If there's too much of a gap between the last entry and the next one, just ditch the run
            if ($this->lastTimestamp instanceof Carbon &&
                ($seconds = $this->lastTimestamp->diffInSeconds($combatLogEntry->getParsedTimestamp())) > self::MAX_TIMESTAMP_GAP_SECONDS) {
                $this->log->parseCombatLogEventTooBigTimestampGap(
                    $seconds,
                    $this->lastTimestamp->toDateTimeString(),
                    $combatLogEntry->getParsedTimestamp()->toDateTimeString()
                );

                // Reset variables
                $this->resetCurrentChallengeMode();

                return $parsedEvent;
            }

            // If the zone changes again, and it matches up, log that we're capturing again and reset the flag
            if ($parsedEvent instanceof ZoneChangeEvent &&
                $parsedEvent->getZoneId() === $this->currentDungeon->map_id &&
                $this->isInWrongZone) {
                // Reset the flag
                $this->isInWrongZone       = false;
                // Capture the zone change though!
                $this->lastZoneChangeEvent = $parsedEvent;

                $this->log->parseCombatLogEventZoneChangeMismatchResolved();
            }

            // Did someone hearth out in the middle of a run?
            if ($this->lastZoneChangeEvent instanceof ZoneChangeEvent &&
                // This is correct - zoneID in the combatlog is mapID in the database/in WCDB stuff
                $this->lastZoneChangeEvent->getZoneId() !== $this->currentDungeon->map_id) {
                // Don't spam this to death - just log this once
                if (!$this->isInWrongZone) {
                    $this->isInWrongZone = true;

                    $this->log->parseCombatLogEventZoneChangeMismatch(
                        $this->lastZoneChangeEvent->getZoneId(),
                        $this->lastZoneChangeEvent->getZoneName(),
                        $this->currentDungeon->zone_id,
                        __($this->currentDungeon->name, [], 'en_US')
                    );
                }

                // Return - consume the event, we don't want events from whatever place you hearthed to in our dungeon
                return $parsedEvent;
            }


            // Save ALL events that come through after the challenge mode start event has been given
            $this->rawEvents->push($rawEvent);
            $this->lastTimestamp = $combatLogEntry->getParsedTimestamp();

            // And it's ended
            if ($parsedEvent instanceof ChallengeModeEndEvent) {
                $saveFilePath = $this->generateTargetCombatLogFileName($this->filePath);

                try {
                    $this->combatLogService->saveCombatLogToFile($this->rawEvents, $saveFilePath);

                    // Add the .txt to a .zip
                    $compressedTargetFilePath = $this->combatLogService->compressCombatLog($saveFilePath);
                    $this->result->push($compressedTargetFilePath);

                    // Reset variables
                    $this->resetCurrentChallengeMode();
                } finally {
                    // remove the .txt
                    unlink($saveFilePath);
                }
            }
        } // If we're going to start a challenge mode event
        else if ($parsedEvent instanceof ChallengeModeStartEvent) {
            $this->log->parseCombatLogEventChallengeModeStartEvent();

            $this->lastChallengeModeStartEvent = $parsedEvent;
            $this->currentDungeon              = Dungeon::firstWhere('challenge_mode_id', $parsedEvent->getChallengeModeID());

            $this->rawEvents->push($this->lastCombatLogVersionEvent->getRawEvent());
            $this->rawEvents->push($this->lastMapChangeEvent->getRawEvent());
            $this->rawEvents->push($this->lastZoneChangeEvent->getRawEvent());
            $this->rawEvents->push($rawEvent);
        }

        // Always keep track of these events
        if ($parsedEvent instanceof CombatLogVersionEvent) {
            $this->log->parseCombatLogEventCombatLogVersionEvent();
            $this->lastCombatLogVersionEvent = $parsedEvent;
        } else if ($parsedEvent instanceof ZoneChangeEvent) {
            $this->log->parseCombatLogEventZoneChangeEvent();
            $this->lastZoneChangeEvent = $parsedEvent;
        } else if ($parsedEvent instanceof MapChangeEvent) {
            $this->log->parseCombatLogEventMapChangeEvent();
            $this->lastMapChangeEvent = $parsedEvent;
        }

        return $parsedEvent;
    }


    private function resetCurrentChallengeMode(): void
    {
        $this->log->resetCurrentChallengeMode();

        $this->rawEvents                   = collect();
        $this->lastTimestamp               = null;
        $this->lastChallengeModeStartEvent = null;
    }

    private function reset(): void
    {
        $this->log->reset();
        $this->resetCurrentChallengeMode();

        $this->lastCombatLogVersionEvent = null;
        $this->lastZoneChangeEvent       = null;
        $this->lastMapChangeEvent        = null;
        $this->result                    = collect();
    }

    protected function getCombatLogFileName(string $countStr): string
    {
        return sprintf('%s_%d_%s%s',
            pathinfo($this->filePath, PATHINFO_FILENAME),
            $this->lastChallengeModeStartEvent->getKeystoneLevel(),
            Str::slug($this->lastChallengeModeStartEvent->getZoneName()),
            $countStr
        );
    }
}
