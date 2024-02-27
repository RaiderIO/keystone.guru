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
use App\Models\Floor\Floor;
use App\Models\Npc;
use App\Service\CombatLog\Logging\CombatLogDataExtractionServiceLoggingInterface;
use App\Service\CombatLog\Models\ExtractedDataResult;
use App\Service\Season\SeasonServiceInterface;

class CombatLogDataExtractionService implements CombatLogDataExtractionServiceInterface
{
    public function __construct(private readonly CombatLogServiceInterface $combatLogService, private readonly SeasonServiceInterface $seasonService, private readonly CombatLogDataExtractionServiceLoggingInterface $log)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function extractData(string $filePath): ExtractedDataResult
    {
        $targetFilePath = $this->combatLogService->extractCombatLog($filePath) ?? $filePath;

        /** @var Dungeon|null $dungeon */
        $dungeon = null;
        /** @var Floor|null $currentFloor */
        $currentFloor    = null;
        $checkedNpcIds   = collect();
        $currentKeyLevel = 1;
        /** @var AffixGroup|null $currentKeyAffixGroup */
        $currentKeyAffixGroup = null;

        $result = new ExtractedDataResult();

        $this->combatLogService->parseCombatLog($targetFilePath, function (int $combatLogVersion, string $rawEvent, int $lineNr) use (&$result, &$dungeon, &$currentFloor, &$checkedNpcIds, &$currentKeyLevel, &$currentKeyAffixGroup) {
            $this->log->addContext('lineNr', ['combatLogVersion' => $combatLogVersion, 'rawEvent' => $rawEvent, 'lineNr' => $lineNr]);

            $combatLogEntry = (new CombatLogEntry($rawEvent));
            $parsedEvent    = $combatLogEntry->parseEvent([], $combatLogVersion);

            if ($combatLogEntry->getParsedTimestamp() === null) {
                $this->log->extractDataTimestampNotSet();

                return $parsedEvent;
            }

            // One way or another, enforce we extract the dungeon from the combat log
            if ($parsedEvent instanceof ChallengeModeStart) {
                $dungeon = Dungeon::where('challenge_mode_id', $parsedEvent->getChallengeModeID())->firstOrFail();

                $currentKeyLevel = $parsedEvent->getKeystoneLevel();

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

                $this->log->extractDataSetChallengeMode(__($dungeon->name, [], 'en-US'), $currentKeyLevel, $currentKeyAffixGroup->getTextAttribute());
            } else if ($parsedEvent instanceof ZoneChange) {
                if ($currentKeyLevel !== 1) {
                    $this->log->extractDataSetZoneFailedChallengeModeActive();
                } else {
                    $dungeon = Dungeon::where('map_id', $parsedEvent->getZoneId())->firstOrFail();

                    $this->log->extractDataSetZone(__($dungeon->name, [], 'en-US'));
                }
            }

            // Ensure we know the floor
            if ($parsedEvent instanceof MapChange) {
                $previousFloor = $currentFloor;

                $currentFloor = Floor::findByUiMapId($parsedEvent->getUiMapID(), $dungeon->id);

                $newIngameMinX = round($parsedEvent->getXMin(), 2);
                $newIngameMinY = round($parsedEvent->getYMin(), 2);
                $newIngameMaxX = round($parsedEvent->getXMax(), 2);
                $newIngameMaxY = round($parsedEvent->getYMax(), 2);

                // Ensure we have the correct bounds for a floor while we're at it
                if ($newIngameMinX !== $currentFloor->ingame_min_x || $newIngameMinY !== $currentFloor->ingame_min_y ||
                    $newIngameMaxX !== $currentFloor->ingame_max_x || $newIngameMaxY !== $currentFloor->ingame_max_y) {
                    $currentFloor->update([
                        'ingame_min_x' => $newIngameMinX,
                        'ingame_min_y' => $newIngameMinY,
                        'ingame_max_x' => $newIngameMaxX,
                        'ingame_max_y' => $newIngameMaxY,
                    ]);
                    $result->updatedFloor();
                }

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

                if ($guid instanceof Creature && $checkedNpcIds->search($guid->getId()) === false) {
                    $npc = Npc::find($guid->getId());

                    if ($npc === null) {
                        $this->log->extractDataNpcNotFound($guid->getId());
                    } else {
                        // Calculate the base health based on the current key level + current max hp
                        $newBaseHealth = (int)($parsedEvent->getAdvancedData()->getMaxHP() / $npc->getScalingFactor(
                                $currentKeyLevel,
                                $currentKeyAffixGroup?->hasAffix(Affix::AFFIX_FORTIFIED) ?? false,
                                $currentKeyAffixGroup?->hasAffix(Affix::AFFIX_TYRANNICAL) ?? false,
                                $currentKeyAffixGroup?->hasAffix(Affix::AFFIX_THUNDERING) ?? false,
                            ));

                        if ($npc->base_health !== $newBaseHealth) {
                            $npc->update([
                                'base_health' => $newBaseHealth,
                            ]);

                            $result->updatedNpc();

                            $this->log->extractDataUpdatedNpc($newBaseHealth);
                        }

                        $checkedNpcIds->push($npc->id);
                    }
                }
            }

            return $parsedEvent;
        });

        return $result;
    }
}
