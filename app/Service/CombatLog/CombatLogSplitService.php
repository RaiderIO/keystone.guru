<?php

namespace App\Service\CombatLog;

use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeEnd as ChallengeModeEndEvent;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeStart as ChallengeModeStartEvent;
use App\Logic\CombatLog\SpecialEvents\CombatLogVersion as CombatLogVersionEvent;
use App\Logic\CombatLog\SpecialEvents\MapChange as MapChangeEvent;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;
use App\Logic\CombatLog\SpecialEvents\ZoneChange as ZoneChangeEvent;
use App\Service\CombatLog\Logging\CombatLogSplitServiceLoggingInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CombatLogSplitService implements CombatLogSplitServiceInterface
{
    private const MAX_TIMESTAMP_GAP_SECONDS = 10 * 60;

    private const EVENTS_TO_KEEP = [
        SpecialEvent::SPECIAL_EVENT_COMBAT_LOG_VERSION,
        SpecialEvent::SPECIAL_EVENT_ZONE_CHANGE,
        SpecialEvent::SPECIAL_EVENT_MAP_CHANGE,
        SpecialEvent::SPECIAL_EVENT_CHALLENGE_MODE_START,
        SpecialEvent::SPECIAL_EVENT_CHALLENGE_MODE_END,
    ];

    /** @var Collection|string[] */
    private Collection $rawEvents;

    private ?string $lastCombatLogVersion;

    private ?ChallengeModeStartEvent $lastChallengeModeStartEvent = null;

    private ?string $lastZoneChange;

    private ?string $lastMapChange;

    private ?Carbon $lastTimestamp = null;

    public function __construct(
        private readonly CombatLogServiceInterface $combatLogService,
        private readonly CombatLogSplitServiceLoggingInterface $log)
    {
        $this->reset();
    }

    public function splitCombatLogOnChallengeModes(string $filePath): Collection
    {
        $this->log->splitCombatLogOnChallengeModesStart($filePath);
        try {
            $targetFilePath = $this->combatLogService->extractCombatLog($filePath) ?? $filePath;
            $result = collect();
            // We don't need to do anything if there are no runs
            // If there's one run, we may still want to trim the fat of the log and keep just
            // the one challenge mode that's in there
            if ($this->combatLogService->getChallengeModes($targetFilePath)->count() <= 0) {
                $this->log->splitCombatLogOnChallengeModesNoChallengeModesFound();

                return $result;
            }

            $this->combatLogService->parseCombatLog($targetFilePath, function (int $combatLogVersion, string $rawEvent, int $lineNr) use ($filePath, &$result) {
                $this->log->addContext('lineNr', ['combatLogVersion' => $combatLogVersion, 'rawEvent' => $rawEvent, 'lineNr' => $lineNr]);

                $combatLogEntry = (new CombatLogEntry($rawEvent));
                $parsedEvent = $combatLogEntry->parseEvent(self::EVENTS_TO_KEEP, $combatLogVersion);

                if ($combatLogEntry->getParsedTimestamp() === null) {
                    $this->log->splitCombatLogOnChallengeModesTimestampNotSet();

                    return $parsedEvent;
                }

                // If we have started a challenge mode
                if ($this->lastChallengeModeStartEvent instanceof ChallengeModeStartEvent) {
                    // If there's too much of a gap between the last entry and the next one, just ditch the run
                    if ($this->lastTimestamp instanceof Carbon &&
                        ($seconds = $this->lastTimestamp->diffInSeconds($combatLogEntry->getParsedTimestamp())) > self::MAX_TIMESTAMP_GAP_SECONDS) {
                        $this->log->splitCombatLogOnChallengeModesTooBigTimestampGap(
                            $seconds,
                            $this->lastTimestamp->toDateTimeString(),
                            $combatLogEntry->getParsedTimestamp()->toDateTimeString()
                        );

                        // Reset variables
                        $this->resetCurrentChallengeMode();

                        return $parsedEvent;
                    }

                    // Save ALL events that come through after the challenge mode start event has been given
                    $this->rawEvents->push($rawEvent);
                    $this->lastTimestamp = $combatLogEntry->getParsedTimestamp();

                    // And it's ended
                    if ($parsedEvent instanceof ChallengeModeEndEvent) {
                        $saveFilePath = $this->generateTargetCombatLogFileName($filePath);

                        $this->combatLogService->saveCombatLogToFile($this->rawEvents, $saveFilePath);

                        // Add the .txt to a .zip
                        $compressedTargetFilePath = $this->combatLogService->compressCombatLog($saveFilePath);
                        $result->push($compressedTargetFilePath);

                        // remove the .txt
                        unlink($saveFilePath);

                        // Reset variables
                        $this->resetCurrentChallengeMode();
                    }
                } // If we're going to start a challenge mode event
                elseif ($parsedEvent instanceof ChallengeModeStartEvent) {
                    $this->log->splitCombatLogOnChallengeModesChallengeModeStartEvent();

                    $this->lastChallengeModeStartEvent = $parsedEvent;

                    $this->rawEvents->push($this->lastCombatLogVersion);
                    $this->rawEvents->push($this->lastZoneChange);
                    $this->rawEvents->push($this->lastMapChange);
                    $this->rawEvents->push($rawEvent);
                }

                // Always keep track of these events
                if ($parsedEvent instanceof CombatLogVersionEvent) {
                    $this->log->splitCombatLogOnChallengeModesCombatLogVersionEvent();
                    $this->lastCombatLogVersion = $rawEvent;
                } elseif ($parsedEvent instanceof ZoneChangeEvent) {
                    $this->log->splitCombatLogOnChallengeModesZoneChangeEvent();
                    $this->lastZoneChange = $rawEvent;
                } elseif ($parsedEvent instanceof MapChangeEvent) {
                    $this->log->splitCombatLogOnChallengeModesMapChangeEvent();
                    $this->lastMapChange = $rawEvent;
                }

                return $parsedEvent;
            });

            if ($this->lastChallengeModeStartEvent !== null) {
                $this->log->splitCombatLogOnChallengeModesLastRunNotCompleted();
            }

            $this->reset();
        } finally {
            $this->log->splitCombatLogOnChallengeModesEnd();
        }

        return $result;
    }

    private function resetCurrentChallengeMode(): void
    {
        $this->log->resetCurrentChallengeMode();

        $this->rawEvents = collect();
        $this->lastTimestamp = null;
        $this->lastChallengeModeStartEvent = null;
    }

    private function reset(): void
    {
        $this->log->reset();
        $this->resetCurrentChallengeMode();

        $this->lastCombatLogVersion = null;
        $this->lastZoneChange = null;
        $this->lastMapChange = null;
    }

    /**
     * Based on the currently known information (as for what dungeon we're doing), generate a file path
     * to save the current combat log at.
     */
    private function generateTargetCombatLogFileName(string $originalFilePath): string
    {
        // Use $filePath here since it's the original location of the .txt/.zip file. We may be reading
        // the combat log ($targetFilePath) from a completely different location. We want to save the
        // new combat log in the original location instead of the location we're reading from.
        $count = 0;
        do {
            $countStr = $count === 0 ? '' : sprintf('-%d', $count);
            $saveFilePath = sprintf('%s/%s_%d_%s%s.txt',
                dirname($originalFilePath),
                pathinfo($originalFilePath, PATHINFO_FILENAME),
                $this->lastChallengeModeStartEvent->getKeystoneLevel(),
                Str::slug($this->lastChallengeModeStartEvent->getZoneName()),
                $countStr
            );

            $this->log->generateTargetCombatLogFileNameAttempt($saveFilePath);
            // While we have a zip file that already exists, someone may have done two
            // the same dungeons of the same key level
            $count++;
        } while (file_exists(str_replace('.txt', '.zip', $saveFilePath)));

        return $saveFilePath;
    }
}
