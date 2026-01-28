<?php

namespace App\Service\CombatLog;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeStart;
use App\Logic\CombatLog\SpecialEvents\ZoneChange;
use App\Models\AffixGroup\AffixGroup;
use App\Models\CombatLog\CombatLogAnalyze;
use App\Models\CombatLog\CombatLogAnalyzeStatus;
use App\Models\Dungeon;
use App\Repositories\Interfaces\Floor\FloorRepositoryInterface;
use App\Service\CombatLog\DataExtractors\CreateMissingNpcDataExtractor;
use App\Service\CombatLog\DataExtractors\DataExtractorInterface;
use App\Service\CombatLog\DataExtractors\FloorDataExtractor;
use App\Service\CombatLog\DataExtractors\NpcUpdateDataExtractor;
use App\Service\CombatLog\DataExtractors\SpellDataExtractor;
use App\Service\CombatLog\Dtos\DataExtraction\DataExtractionCurrentDungeon;
use App\Service\CombatLog\Dtos\DataExtraction\ExtractedDataResult;
use App\Service\CombatLog\Logging\CombatLogDataExtractionServiceLoggingInterface;
use App\Service\Season\SeasonServiceInterface;
use App\Service\Wowhead\WowheadServiceInterface;
use Exception;
use Illuminate\Support\Collection;

class CombatLogDataExtractionService implements CombatLogDataExtractionServiceInterface
{
    /**
     * @var int[] Additional NPC IDs that are summoned but do not have a SUMMON combat log event.
     */
    public const SUMMONED_NPC_IDS = [
        // Storm, Earth and Fire talent (Monk)
        69791,
        // Fire Spirit
        69792,
        // Earth Spirit
    ];

    /** @var Collection<DataExtractorInterface> */
    private readonly Collection $dataExtractors;

    public function __construct(
        private readonly CombatLogServiceInterface                      $combatLogService,
        private readonly SeasonServiceInterface                         $seasonService,
        private readonly WowheadServiceInterface                        $wowheadService,
        private readonly FloorRepositoryInterface                       $floorRepository,
        private readonly CombatLogDataExtractionServiceLoggingInterface $log,
    ) {
        $this->dataExtractors = collect([
            new CreateMissingNpcDataExtractor(),
            new NpcUpdateDataExtractor(),
            new FloorDataExtractor($this->floorRepository),
            new SpellDataExtractor($this->wowheadService),
        ]);
    }

    public function extractData(string $filePath, ?callable $onProcessLine = null): ExtractedDataResult
    {
        $targetFilePath = $this->combatLogService->extractCombatLog($filePath) ?? $filePath;

        $currentDungeon = null;

        $result = new ExtractedDataResult();

        foreach ($this->dataExtractors as $dataExtractor) {
            $dataExtractor->beforeExtract($result, $filePath);
        }

        $this->combatLogService->parseCombatLog($targetFilePath, function (
            int    $combatLogVersion,
            bool   $advancedLoggingEnabled,
            string $rawEvent,
            int    $lineNr,
        ) use (&$result, &$currentDungeon, &$currentFloor, &$checkedNpcIds, $onProcessLine) {
            // We don't care if there's no advanced logging enabled!
            if (!$advancedLoggingEnabled) {
                return null;
            }

            $this->log->addContext('lineNr', [
                'combatLogVersion' => $combatLogVersion,
                'rawEvent'         => trim($rawEvent),
                'lineNr'           => $lineNr,
            ]);

            $combatLogEntry = (new CombatLogEntry($rawEvent));

            $parsedEvent = $combatLogEntry->parseEvent([], $combatLogVersion);

            if ($onProcessLine !== null) {
                $onProcessLine($lineNr, $parsedEvent);
            }

            // This shouldn't be called - it throws an exception before when this happens?
            if ($combatLogEntry->getParsedTimestamp() === null) {
                $this->log->extractDataTimestampNotSet();

                return null;
            }

            // Override the current data if we can, otherwise default back to whatever we parsed before
            $currentDungeon = $this->extractDungeon($currentDungeon, $parsedEvent) ?? $currentDungeon;

            if ($currentDungeon === null) {
                $this->log->extractDataDungeonNotSet();

                return $parsedEvent;
            }

            foreach ($this->dataExtractors as $dataExtractor) {
                $dataExtractor->extractData($result, $currentDungeon, $parsedEvent);
            }

            return $parsedEvent;
        });

        // Remove the lineNr context since we stopped parsing lines, don't let the last line linger in the context
        $this->log->removeContext('lineNr');

        foreach ($this->dataExtractors as $dataExtractor) {
            $dataExtractor->afterExtract($result, $filePath);
        }

        return $result;
    }

    private function extractDungeon(
        ?DataExtractionCurrentDungeon $currentDungeon,
        BaseEvent                     $parsedEvent,
    ): ?DataExtractionCurrentDungeon {
        $result = null;

        // One way or another, enforce we extract the dungeon from the combat log
        if ($parsedEvent instanceof ChallengeModeStart) {
            $dungeon = Dungeon::where('challenge_mode_id', $parsedEvent->getChallengeModeID())->firstOrFail();

            $currentKeyLevel      = $parsedEvent->getKeystoneLevel();
            $currentKeyAffixGroup = null;

            // Find the correct affix groups that match the affix combination the dungeon was started with
            $currentSeasonForDungeon = $dungeon->getActiveSeason($this->seasonService);
            if ($currentSeasonForDungeon !== null) {
                $affixGroups = AffixGroup::findMatchingAffixGroupsForAffixIds(
                    $currentSeasonForDungeon,
                    collect($parsedEvent->getAffixIDs()),
                );

                /** @var AffixGroup|null $currentKeyAffixGroup */
                $currentKeyAffixGroup = $affixGroups->first();
            }

            $result = new DataExtractionCurrentDungeon($dungeon, $currentKeyLevel, $currentKeyAffixGroup);

            $this->log->extractDataSetChallengeMode(
                __($dungeon->name, [], 'en_US'),
                $currentKeyLevel,
                $currentKeyAffixGroup?->getTextAttribute(),
            );
        } elseif ($parsedEvent instanceof ZoneChange) {
            if ($currentDungeon?->keyLevel !== null) {
                $this->log->extractDataSetZoneFailedChallengeModeActive();
            } else {
                $dungeon = Dungeon::firstWhere('map_id', $parsedEvent->getZoneId());

                if ($dungeon === null) {
                    $this->log->extractDataZoneChangeDungeonNotFound($parsedEvent->getZoneId(), $parsedEvent->getZoneName());
                } else {
                    $result = new DataExtractionCurrentDungeon($dungeon);

                    $this->log->extractDataZoneChangeSetZone(__($dungeon->name, [], 'en_US'));
                }
            }
        }

        return $result;
    }

    public function extractDataAsync(string $filePath, CombatLogAnalyze $combatLogAnalyze): ?ExtractedDataResult
    {
        $result = null;

        try {
            $this->log->extractDataAsyncStart($filePath, $combatLogAnalyze->id);

            $this->log->extractDataAsyncVerifying();
            $combatLogAnalyze->update([
                'status' => CombatLogAnalyzeStatus::Verifying,
            ]);

            $totalLines = 0;

            try {
                $this->combatLogService->parseCombatLog($filePath, function (
                    int    $combatLogVersion,
                    bool   $advancedLoggingEnabled,
                    string $rawEvent,
                ) use (&$totalLines) {
                    $totalLines++;

                    return new CombatLogEntry($rawEvent)->parseEvent([], $combatLogVersion);
                });
            } catch (Exception $e) {
                $this->log->extractDataAsyncVerifyError($e);

                $combatLogAnalyze->update([
                    'status' => CombatLogAnalyzeStatus::Error,
                    'error'  => __('services.combatlogservice.analyze_combat_log.verify_error', [
                        'error' => $e->getMessage(),
                    ]),
                ]);

                return null;
            }

            try {
                $this->log->extractDataAsyncProcessing();
                $combatLogAnalyze->update([
                    'status' => CombatLogAnalyzeStatus::Processing,
                ]);

                $result = $this->extractData($filePath, function (int $lineNr) use ($totalLines, $combatLogAnalyze) {
                    $progressPercent = (int)(($lineNr / $totalLines) * 100);
                    if ($progressPercent !== $combatLogAnalyze->percent_completed && $progressPercent % 5 === 0) {
                        $this->log->extractDataAsyncAnalyzeProgress($progressPercent, $lineNr, $totalLines);

                        $combatLogAnalyze->update([
                            'percent_completed' => $progressPercent,
                        ]);
                    }
                });
                $combatLogAnalyze->update([
                    'percent_completed' => 100,
                    'result'            => json_encode($result->toArray()),
                ]);
            } catch (Exception $e) {
                $this->log->extractDataAsyncAnalyzeError($e);

                $combatLogAnalyze->update([
                    'status' => CombatLogAnalyzeStatus::Error,
                    'error'  => __('services.combatlogservice.analyze_combat_log.processing_error', [
                        'error' => $e->getMessage(),
                    ]),
                ]);

                return null;
            } finally {
                $this->log->extractDataAsyncCompleted();

                $combatLogAnalyze->update([
                    'status' => CombatLogAnalyzeStatus::Completed,
                ]);
            }
        } finally {
            $this->log->extractDataAsyncEnd();
        }

        return $result;
    }
}
