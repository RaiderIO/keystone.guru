<?php

namespace App\Service\CombatLog;

use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\Guid\Creature;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeStart;
use App\Logic\CombatLog\SpecialEvents\MapChange;
use App\Logic\CombatLog\SpecialEvents\ZoneChange;
use App\Models\Affix;
use App\Models\AffixGroup\AffixGroup;
use App\Models\Dungeon;
use App\Models\Floor;
use App\Models\Npc;
use App\Service\CombatLog\Logging\CombatLogDataExtractionServiceLoggingInterface;
use App\Service\CombatLog\Models\ExtractedDataResult;
use App\Service\Season\SeasonServiceInterface;

class CombatLogDataExtractionService implements CombatLogDataExtractionServiceInterface
{

    private CombatLogServiceInterface $combatLogService;

    private SeasonServiceInterface $seasonService;

    private CombatLogDataExtractionServiceLoggingInterface $log;

    /**
     * @param CombatLogServiceInterface                      $combatLogService
     * @param SeasonServiceInterface                         $seasonService
     * @param CombatLogDataExtractionServiceLoggingInterface $log
     */
    public function __construct(
        CombatLogServiceInterface                      $combatLogService,
        SeasonServiceInterface                         $seasonService,
        CombatLogDataExtractionServiceLoggingInterface $log
    )
    {
        $this->combatLogService = $combatLogService;
        $this->seasonService    = $seasonService;
        $this->log              = $log;
    }

    /**
     * @inheritDoc
     */
    public function extractData(string $filePath): ExtractedDataResult
    {
        $targetFilePath = $this->combatLogService->extractCombatLog($filePath) ?? $filePath;

        /** @var Dungeon|null $dungeon */
        $dungeon = null;
        /** @var Floor|null $currentFloor */
        $currentFloor    = null;
        $updatedNpcIds   = collect();
        $currentKeyLevel = 1;
        /** @var AffixGroup|null $currentKeyAffixGroup */
        $currentKeyAffixGroup = null;

        $result = new ExtractedDataResult();

        $this->combatLogService->parseCombatLog($targetFilePath, function (string $rawEvent, int $lineNr)
        use ($targetFilePath, &$result, &$dungeon, &$currentFloor, &$updatedNpcIds, &$currentKeyLevel, &$currentKeyAffixGroup) {
            $this->log->addContext('lineNr', ['rawEvent' => $rawEvent, 'lineNr' => $lineNr]);

            $combatLogEntry = (new CombatLogEntry($rawEvent));
            $parsedEvent    = $combatLogEntry->parseEvent();

            if ($combatLogEntry->getParsedTimestamp() === null) {
                $this->log->extractDataTimestampNotSet();

                return;
            }


            // One way or another, enforce we extract the dungeon from the combat log
            if ($parsedEvent instanceof ChallengeModeStart) {
                $dungeon = Dungeon::where('map_id', $parsedEvent->getInstanceID())->firstOrFail();

                $currentKeyLevel = $parsedEvent->getKeystoneLevel();


                // Find the correct affix groups that match the affix combination the dungeon was started with
                $currentSeasonForDungeon = $dungeon->getActiveSeason($this->seasonService);
                if ($currentSeasonForDungeon !== null) {
                    $affixGroups = AffixGroup::findMatchingAffixGroupsForAffixIds($currentSeasonForDungeon, $parsedEvent->getAffixIDs());

                    /** @var AffixGroup|null $currentKeyAffixGroup */
                    $currentKeyAffixGroup = $affixGroups->first();
                }

                $this->log->extractDataSetChallengeMode(__($dungeon->name, [], 'en'), $currentKeyLevel, $currentKeyAffixGroup->getTextAttribute());
            } else if ($parsedEvent instanceof ZoneChange) {
                $dungeon = Dungeon::where('map_id', $parsedEvent->getZoneId())->firstOrFail();

                $this->log->extractDataSetZone(__($dungeon->name, [], 'en'));
            }

            // Ensure we know the floor
            if ($parsedEvent instanceof MapChange) {
                $previousFloor = $currentFloor;

                $currentFloor = Floor::findByUiMapId($parsedEvent->getUiMapID());

                // Ensure we have the correct bounds for a floor while we're at it
                $currentFloor->update([
                    'ingame_min_x' => round($parsedEvent->getXMin(), 2),
                    'ingame_min_y' => round($parsedEvent->getYMin(), 2),
                    'ingame_max_x' => round($parsedEvent->getXMax(), 2),
                    'ingame_max_y' => round($parsedEvent->getYMax(), 2),
                ]);
                $result->updatedFloor();

                if ($previousFloor !== null && $previousFloor !== $currentFloor) {
                    $assignedFloor = $previousFloor->ensureConnectionToFloor($currentFloor);
                    $assignedFloor = $currentFloor->ensureConnectionToFloor($previousFloor) || $assignedFloor;

                    if ($assignedFloor) {
                        $result->updatedFloorConnection();

                        $this->log->extractDataAddedNewFloorConnection(
                            $previousFloor->id,
                            $currentFloor->id
                        );
                    }
                }
            }

            if ($parsedEvent instanceof AdvancedCombatLogEvent) {
                $guid = $parsedEvent->getAdvancedData()->getInfoGuid();

                if ($guid instanceof Creature && $updatedNpcIds->search($guid->getId()) === false) {
                    $npc = Npc::find($guid->getId());

                    if ($npc === null) {
                        $this->log->extractDataNpcNotFound($guid->getId());
                    } else {
                        // Update the NPC's max health
                        $npc->update([
                            'base_health' => $npc->calculateHealthForKey(
                                $currentKeyLevel,
                                optional($currentKeyAffixGroup)->hasAffix(Affix::AFFIX_FORTIFIED),
                                optional($currentKeyAffixGroup)->hasAffix(Affix::AFFIX_TYRANNICAL),
                                optional($currentKeyAffixGroup)->hasAffix(Affix::AFFIX_THUNDERING),
                            )
                        ]);


                        $updatedNpcIds->push($npc->id);
                        $result->updatedNpc();

                        $this->log->extractDataUpdatedNpc($npc->base_health);
                    }
                }
            }
        });

        return $result;
    }
}
