<?php

namespace App\Service\CombatLog;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatEvents\CombatLogEvent;
use App\Logic\CombatLog\CombatEvents\Prefixes\Prefix;
use App\Logic\CombatLog\CombatEvents\Prefixes\Spell;
use App\Logic\CombatLog\CombatEvents\Suffixes\AuraApplied;
use App\Logic\CombatLog\CombatEvents\Suffixes\Suffix;
use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\Guid\Creature;
use App\Logic\CombatLog\Guid\Player;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeStart;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;
use App\Logic\CombatLog\SpecialEvents\ZoneChange;
use App\Models\AffixGroup\AffixGroup;
use App\Models\Dungeon;
use App\Service\CombatLog\DataExtractors\CreateMissingNpcDataExtractor;
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
            new CreateMissingNpcDataExtractor(),
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
            $this->log->addContext('lineNr', ['combatLogVersion' => $combatLogVersion, 'rawEvent' => trim($rawEvent), 'lineNr' => $lineNr]);

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

        // Remove the lineNr context since we stopped parsing lines, don't let the last line linger in the context
        $this->log->removeContext('lineNr');

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

            $this->log->extractDataSetChallengeMode(
                __($dungeon->name, [], 'en_US'),
                $currentKeyLevel,
                optional($currentKeyAffixGroup)->getTextAttribute()
            );
        } else if ($parsedEvent instanceof ZoneChange) {
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

    public function extractSpellAuraIds(string $filePath): Collection
    {
        $targetFilePath = $this->combatLogService->extractCombatLog($filePath) ?? $filePath;

        $currentDungeon = null;

        $result = collect();

        $this->combatLogService->parseCombatLog($targetFilePath, function (int $combatLogVersion, string $rawEvent, int $lineNr)
        use (&$result, &$currentDungeon, &$currentFloor, &$checkedNpcIds) {
            $this->log->addContext('lineNr', ['combatLogVersion' => $combatLogVersion, 'rawEvent' => trim($rawEvent), 'lineNr' => $lineNr]);

            $combatLogEntry = (new CombatLogEntry($rawEvent));
            $parsedEvent    = $combatLogEntry->parseEvent([
                SpecialEvent::SPECIAL_EVENT_CHALLENGE_MODE_START,
                SpecialEvent::SPECIAL_EVENT_ZONE_CHANGE,
                // Only SPELL_AURA_APPLIED
                sprintf('%s_%s', Prefix::PREFIX_SPELL, Suffix::SUFFIX_AURA_APPLIED),
            ], $combatLogVersion);

            if ($parsedEvent === null) {
                // Do not log - that'd spam with 1000s of log lines
                return null;
            }

            // Override the current data if we can, otherwise default back to whatever we parsed before
            $currentDungeon = $this->extractDungeon($currentDungeon, $parsedEvent) ?? $currentDungeon;

            if ($currentDungeon === null) {
                $this->log->extractSpellAuraIdsDungeonNotSet();

                return $parsedEvent;
            }

            $spellIdsForDungeon = $result->get($currentDungeon->dungeon->id);
            if ($spellIdsForDungeon === null) {
                $result->put($currentDungeon->dungeon->id, $spellIdsForDungeon = collect());
            }

            if ($parsedEvent instanceof CombatLogEvent) {
                $prefix = $parsedEvent->getPrefix();
                $suffix = $parsedEvent->getSuffix();
                if ($prefix instanceof Spell && $suffix instanceof AuraApplied) {
                    $sourceGuid = $parsedEvent->getGenericData()->getSourceGuid();
                    if ($sourceGuid instanceof Creature &&
                        // Only actual creatures - not pets
                        $sourceGuid->getUnitType() === Creature::CREATURE_UNIT_TYPE_CREATURE) {
                        if (!$spellIdsForDungeon->has($prefix->getSpellId())) {
                            $spellIdsForDungeon->put($prefix->getSpellId(), $parsedEvent);

                            $this->log->extractSpellAuraIdsFoundSpellId($prefix->getSpellId());
                        }
                    }

                }
            }

            return $parsedEvent;
        });

        // Remove the lineNr context since we stopped parsing lines, don't let the last line linger in the context
        $this->log->removeContext('lineNr');

        return $result;
    }
}
