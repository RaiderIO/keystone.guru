<?php

namespace App\Service\CombatLog\DataExtractors;

use App;
use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\Guid\Creature;
use App\Models\Npc\Npc;
use App\Service\CombatLog\CombatLogDataExtractionService;
use App\Service\CombatLog\DataExtractors\Logging\NpcUpdateDataExtractorLoggingInterface;
use App\Service\CombatLog\Dtos\DataExtraction\DataExtractionCurrentDungeon;
use App\Service\CombatLog\Dtos\DataExtraction\ExtractedDataResult;
use Illuminate\Support\Collection;

class NpcUpdateDataExtractor implements DataExtractorInterface
{
    /** @var Collection<int> */
    private Collection $checkedNpcIds;

    private NpcUpdateDataExtractorLoggingInterface $log;

    public function __construct()
    {
        $this->checkedNpcIds = collect(CombatLogDataExtractionService::SUMMONED_NPC_IDS);
        $log                 = App::make(NpcUpdateDataExtractorLoggingInterface::class);
        /** @var NpcUpdateDataExtractorLoggingInterface $log */

        $this->log = $log;
    }

    public function beforeExtract(ExtractedDataResult $result, string $combatLogFilePath): void
    {

    }

    public function extractData(ExtractedDataResult $result, DataExtractionCurrentDungeon $currentDungeon, BaseEvent $parsedEvent): void
    {
        if (!($parsedEvent instanceof AdvancedCombatLogEvent)) {
            return;
        }
        $guid = $parsedEvent->getAdvancedData()->getInfoGuid();

        if ($guid instanceof Creature && $this->checkedNpcIds->search($guid->getId()) === false) {
            $npc = Npc::find($guid->getId());

            if ($npc === null) {
                $this->log->extractDataNpcNotFound($guid->getId());

                $this->checkedNpcIds->push($guid->getId());

                return;
            }

            $this->extractBaseHealth($result, $currentDungeon, $parsedEvent, $npc);

            $this->checkedNpcIds->push($npc->id);
        }
    }

    public function afterExtract(ExtractedDataResult $result, string $combatLogFilePath): void
    {

    }

    private function extractBaseHealth(
        ExtractedDataResult          $result,
        DataExtractionCurrentDungeon $currentDungeon,
        AdvancedCombatLogEvent       $parsedEvent,
        Npc                          $npc): void
    {
        if ($currentDungeon->keyLevel === null) {
            $newBaseHealth = $parsedEvent->getAdvancedData()->getMaxHP();
        } else {
            // @TODO Disabled for now since I think it's calculated incorrectly - we also don't need it now
            $newBaseHealth = $npc->base_health;
            // Calculate the base health based on the current key level + current max hp
//            $newBaseHealth = (int)($parsedEvent->getAdvancedData()->getMaxHP() / $npc->getScalingFactor(
//                    $currentDungeon->keyLevel,
//                    $currentDungeon->affixGroup->affixes->pluck('key')->toArray()
//                ));
        }

        if ($npc->base_health !== $newBaseHealth) {
            $baseHealth = $npc->base_health;

            $npc->update([
                'base_health' => $newBaseHealth,
            ]);

            $result->updatedNpc();

            $this->log->extractDataUpdatedNpc($npc->id, $baseHealth, $newBaseHealth);
        }
    }
}
