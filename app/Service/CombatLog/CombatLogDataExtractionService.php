<?php

namespace App\Service\CombatLog;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeStart;
use App\Logic\CombatLog\SpecialEvents\ZoneChange;
use App\Models\AffixGroup\AffixGroup;
use App\Models\Dungeon;
use App\Service\CombatLog\DataExtractors\DataExtractorInterface;
use App\Service\CombatLog\DataExtractors\FloorDataExtractor;
use App\Service\CombatLog\DataExtractors\NpcUpdateDataExtractor;
use App\Service\CombatLog\Dtos\DataExtraction\DataExtractionCurrentDungeon;
use App\Service\CombatLog\Dtos\DataExtraction\ExtractedDataResult;
use App\Service\CombatLog\Logging\CombatLogDataExtractionServiceLoggingInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Collection;

class CombatLogDataExtractionService implements CombatLogDataExtractionServiceInterface
{
    /** @var Collection<DataExtractorInterface> */
    private Collection $dataExtractors;

    public function __construct(
        private readonly CombatLogServiceInterface                      $combatLogService,
        private readonly SeasonServiceInterface                         $seasonService,
        private readonly CombatLogDataExtractionServiceLoggingInterface $log
    ) {
        $this->dataExtractors = collect([
            new FloorDataExtractor(),
            new NpcUpdateDataExtractor(),
        ]);
    }

    public function extractData(string $filePath): ExtractedDataResult
    {
        $targetFilePath = $this->combatLogService->extractCombatLog($filePath) ?? $filePath;

        $currentDungeon = null;

        $result = new ExtractedDataResult();

        $this->combatLogService->parseCombatLog($targetFilePath, function (int $combatLogVersion, string $rawEvent, int $lineNr)
        use (&$result, &$currentDungeon, &$currentFloor, &$checkedNpcIds) {
            $this->log->addContext('lineNr', ['combatLogVersion' => $combatLogVersion, 'rawEvent' => $rawEvent, 'lineNr' => $lineNr]);

            $combatLogEntry = (new CombatLogEntry($rawEvent));
            $parsedEvent    = $combatLogEntry->parseEvent([], $combatLogVersion);

            if ($combatLogEntry->getParsedTimestamp() === null) {
                $this->log->extractDataTimestampNotSet();

                return $parsedEvent;
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

        return $result;
    }

    private function extractDungeon(?DataExtractionCurrentDungeon $currentDungeon, BaseEvent $parsedEvent): ?DataExtractionCurrentDungeon
    {
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
                    collect($parsedEvent->getAffixIDs())
                );

                /** @var AffixGroup|null $currentKeyAffixGroup */
                $currentKeyAffixGroup = $affixGroups->first();
            }

            $result = new DataExtractionCurrentDungeon($dungeon, $currentKeyLevel, $currentKeyAffixGroup);

            $this->log->extractDataSetChallengeMode(__($dungeon->name, [], 'en_US'), $currentKeyLevel, $currentKeyAffixGroup->getTextAttribute());
        } else if ($parsedEvent instanceof ZoneChange) {
            if ($currentDungeon?->keyLevel !== 1) {
                $this->log->extractDataSetZoneFailedChallengeModeActive();
            } else {
                $dungeon = Dungeon::where('map_id', $parsedEvent->getZoneId())->firstOrFail();

                $result = new DataExtractionCurrentDungeon($dungeon);

                $this->log->extractDataSetZone(__($dungeon->name, [], 'en_US'));
            }
        }

        return $result;
    }
}
