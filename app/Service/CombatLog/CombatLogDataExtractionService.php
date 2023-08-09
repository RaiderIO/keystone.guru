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
use App\Models\DungeonRouteAffixGroup;
use App\Models\Enemy;
use App\Models\Floor;
use App\Models\Npc;
use App\Service\CombatLog\CombatLogDataExtractionServiceInterface;
use App\Service\CombatLog\Models\ExtractedData;
use App\Service\Season\SeasonServiceInterface;

class CombatLogDataExtractionService implements CombatLogDataExtractionServiceInterface
{

    private CombatLogServiceInterface $combatLogService;

    private SeasonServiceInterface $seasonService;

    /**
     * @param CombatLogServiceInterface $combatLogService
     * @param SeasonServiceInterface    $seasonService
     */
    public function __construct(
        CombatLogServiceInterface $combatLogService,
        SeasonServiceInterface    $seasonService
    )
    {
        $this->combatLogService = $combatLogService;
        $this->seasonService    = $seasonService;
    }

    /**
     * @inheritDoc
     */
    public function extractData(string $filePath): ExtractedData
    {
        $targetFilePath = $this->combatLogService->extractCombatLog($filePath) ?? $filePath;

        /** @var Floor|null $currentFloor */
        $currentFloor    = null;
        $updatedNpcIds   = collect();
        $currentKeyLevel = 1;
        /** @var AffixGroup|null $currentKeyAffixGroup */
        $currentKeyAffixGroup = null;

        $this->combatLogService->parseCombatLog($targetFilePath, function (string $rawEvent, int $lineNr)
        use ($targetFilePath, &$mappingVersion, &$dungeon, &$currentFloor, &$updatedNpcIds, &$currentKeyLevel, &$currentKeyAffixGroup) {
            $this->log->addContext('lineNr', ['rawEvent' => $rawEvent, 'lineNr' => $lineNr]);

            $combatLogEntry = (new CombatLogEntry($rawEvent));
            $parsedEvent    = $combatLogEntry->parseEvent();

            if ($combatLogEntry->getParsedTimestamp() === null) {
                $this->log->createMappingVersionFromCombatLogTimestampNotSet();

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
            } else if ($parsedEvent instanceof ZoneChange) {
                $dungeon = Dungeon::where('map_id', $parsedEvent->getZoneId())->firstOrFail();
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

                if ($previousFloor !== null && $previousFloor !== $currentFloor) {
                    $assignedFloor = $previousFloor->ensureConnectionToFloor($currentFloor);
                    $assignedFloor = $currentFloor->ensureConnectionToFloor($previousFloor) || $assignedFloor;

                    if ($assignedFloor) {
                        $this->log->createMappingVersionFromCombatLogAddedNewFloorConnection(
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
                    }
                }
            }
        });


        if ($dungeon === null) {
            $mappingVersion->delete();
        }
    }
}
