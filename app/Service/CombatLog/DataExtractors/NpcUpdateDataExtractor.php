<?php

namespace App\Service\CombatLog\DataExtractors;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Models\Npc\Npc;
use App\Service\CombatLog\Dtos\DataExtraction\DataExtractionCurrentDungeon;
use App\Service\CombatLog\Dtos\DataExtraction\ExtractedDataResult;

/**
 * Placeholder for updating NPC base health from advanced combat log data - see extractBaseHealth() below, which
 * cannot be enabled until the combat log's game version is known. Until then this extractor deliberately does
 * nothing: it used to scan a list of seen NPC ids and issue an Npc::find() per distinct NPC on every advanced
 * event, only to hand the result to a method whose body is commented out.
 */
class NpcUpdateDataExtractor implements DataExtractorInterface
{
    public function beforeExtract(ExtractedDataResult $result, string $combatLogFilePath): void
    {
    }

    public function extractData(
        ExtractedDataResult          $result,
        DataExtractionCurrentDungeon $currentDungeon,
        BaseEvent                    $parsedEvent,
    ): void {
    }

    public function afterExtract(ExtractedDataResult $result, string $combatLogFilePath): void
    {
    }

    /** @phpstan-ignore method.unused (retained placeholder for the base health work, see #2449) */
    private function extractBaseHealth(
        ExtractedDataResult          $result,
        DataExtractionCurrentDungeon $currentDungeon,
        AdvancedCombatLogEvent       $parsedEvent,
        Npc                          $npc,
    ): void {
        // This code needs to know the game version of the combat log file, so we can fetch the correct health for the NPC

//        $npcHealth = $npc->getHealthByGameVersion($currentDungeon->dungeon->gameVersion);
//
//        if ($currentDungeon->keyLevel === null) {
//            $newBaseHealth = $parsedEvent->getAdvancedData()->getMaxHP();
//        } else {
//            // @TODO Disabled for now since I think it's calculated incorrectly - we also don't need it now
//            $newBaseHealth = $npcHealth->health;
//            // Calculate the base health based on the current key level + current max hp
        ////            $newBaseHealth = (int)($parsedEvent->getAdvancedData()->getMaxHP() / $npc->getScalingFactor(
        ////                    $currentDungeon->keyLevel,
        ////                    $currentDungeon->affixGroup->affixes->pluck('key')->toArray()
        ////                ));
//        }
//
//        if ($npcHealth === null || $npcHealth->health !== $newBaseHealth) {
//            if ($npcHealth === null) {
//                $npcHealth = NpcHealth::create([
//                    'npc_id'          => $npc->id,
//                    'game_version_id' => $currentDungeon->dungeon->gameVersion->id,
//                    'health'          => $newBaseHealth,
//                ]);
//            } else {
//                $npcHealth->update([
//                    'health' => $newBaseHealth,
//                ]);
//            }
//
//            $baseHealth = $npcHealth?->health;
//
//            $result->updatedNpc();
//
//            $this->log->extractDataUpdatedNpc($npc->id, $baseHealth, $newBaseHealth);
//        }
    }
}
