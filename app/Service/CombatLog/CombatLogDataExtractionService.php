<?php

namespace App\Service\CombatLog;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\SpecialEvents\Interfaces\HasCombatLogDungeonContextInterface;
use App\Logic\CombatLog\SpecialEvents\ZoneChange;
use App\Models\AffixGroup\AffixGroup;
use App\Models\CombatLog\CombatLogAnalyze;
use App\Models\CombatLog\CombatLogAnalyzeStatus;
use App\Models\CombatLog\ParsedCombatLog;
use App\Models\Dungeon;
use App\Repositories\Interfaces\CombatLog\ParsedCombatLogRepositoryInterface;
use App\Repositories\Interfaces\Floor\FloorRepositoryInterface;
use App\Repositories\Interfaces\SpellRepositoryInterface;
use App\Service\CombatLog\DataExtractors\CreateMissingNpcDataExtractor;
use App\Service\CombatLog\DataExtractors\DataExtractorInterface;
use App\Service\CombatLog\DataExtractors\FloorDataExtractor;
use App\Service\CombatLog\DataExtractors\NpcCharacteristicDataExtractor;
use App\Service\CombatLog\DataExtractors\NpcUpdateDataExtractor;
use App\Service\CombatLog\DataExtractors\SpellDataExtractor;
use App\Service\CombatLog\Dtos\CombatLogRunContextInterface;
use App\Service\CombatLog\Dtos\DataExtraction\DataExtractionCurrentDungeon;
use App\Service\CombatLog\Dtos\DataExtraction\ExtractedDataResult;
use App\Service\CombatLog\Logging\CombatLogDataExtractionServiceLoggingInterface;
use App\Service\Season\SeasonServiceInterface;
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
        private readonly FloorRepositoryInterface                       $floorRepository,
        private readonly SpellRepositoryInterface                       $spellRepository,
        private readonly ParsedCombatLogRepositoryInterface             $parsedCombatLogRepository,
        private readonly CombatLogDataExtractionServiceLoggingInterface $log,
    ) {
        $this->dataExtractors = collect([
            new CreateMissingNpcDataExtractor(),
            new NpcUpdateDataExtractor(),
            new FloorDataExtractor($this->floorRepository),
            new SpellDataExtractor(),
            new NpcCharacteristicDataExtractor($this->spellRepository),
        ]);
    }

    public function extractData(
        string                        $filePath,
        ?bool                         $force = null,
        ?callable                     $onProcessLine = null,
        ?CombatLogRunContextInterface $runContext = null,
    ): ?ExtractedDataResult {
        $force ??= false;

        if (!$force && $this->parsedCombatLogRepository->exists(['combat_log_path' => $filePath])) {
            return null;
        }

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
        ) use (&$result, &$currentDungeon, &$currentFloor, &$checkedNpcIds, $onProcessLine, $runContext) {
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
            $currentDungeon = $this->extractDungeon($currentDungeon, $parsedEvent, $runContext) ?? $currentDungeon;

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

        if (!$force) {
            ParsedCombatLog::insert([
                'combat_log_path' => $filePath,
                'extracted_data'  => true,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        return $result;
    }

    private function extractDungeon(
        ?DataExtractionCurrentDungeon $currentDungeon,
        BaseEvent                     $parsedEvent,
        ?CombatLogRunContextInterface $runContext = null,
    ): ?DataExtractionCurrentDungeon {
        $result = null;

        if ($parsedEvent instanceof HasCombatLogDungeonContextInterface) {
            $dungeon  = Dungeon::where('challenge_mode_id', $parsedEvent->getChallengeModeID())->firstOrFail();
            $keyLevel = $parsedEvent->getKeyLevel() ?? $runContext?->getKeyLevel();
            $affixIds = $parsedEvent->getAffixIDs() ?? $runContext?->getAffixIds();

            $affixGroup = null;
            $season     = $dungeon->getActiveSeason($this->seasonService);
            if ($season !== null && $affixIds !== null) {
                /** @var AffixGroup|null $affixGroup */
                $affixGroup = AffixGroup::findMatchingAffixGroupsForAffixIds($season, collect($affixIds))->first();
            }

            $result = new DataExtractionCurrentDungeon($dungeon, $keyLevel, $affixGroup);

            $this->log->extractDataSetDungeon(
                __($dungeon->name, [], 'en_US'),
                $keyLevel,
                $affixGroup?->getTextAttribute(),
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

                $result = $this->extractData($filePath, false, function (int $lineNr) use ($totalLines, $combatLogAnalyze) {
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
