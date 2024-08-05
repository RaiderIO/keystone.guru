<?php

namespace App\Service\CombatLog\DataExtractors;

use App;
use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\Guid\Creature;
use App\Models\Affix;
use App\Models\Npc\Npc;
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
        $this->checkedNpcIds = collect();
        $log                 = App::make(NpcUpdateDataExtractorLoggingInterface::class);
        /** @var NpcUpdateDataExtractorLoggingInterface $log */

        $this->log = $log;
    }

    public function beforeExtract(ExtractedDataResult $result): void
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

            // Determine health
            if ($currentDungeon->keyLevel === null) {
                $newBaseHealth = $parsedEvent->getAdvancedData()->getMaxHP();
            } else {
                // Calculate the base health based on the current key level + current max hp
                $newBaseHealth = (int)($parsedEvent->getAdvancedData()->getMaxHP() / $npc->getScalingFactor(
                        $currentDungeon->keyLevel,
                        $currentDungeon->affixGroup?->hasAffix(Affix::AFFIX_FORTIFIED) ?? false,
                        $currentDungeon->affixGroup?->hasAffix(Affix::AFFIX_TYRANNICAL) ?? false,
                        $currentDungeon->affixGroup?->hasAffix(Affix::AFFIX_THUNDERING) ?? false,
                    ));
            }

            if ($npc->base_health !== $newBaseHealth) {
                $baseHealth = $npc->base_health;

                $npc->update([
                    'base_health' => $newBaseHealth,
                ]);

                $result->updatedNpc();

                $this->log->extractDataUpdatedNpc($baseHealth, $newBaseHealth);
            }

            $this->checkedNpcIds->push($npc->id);

        }
    }

    public function afterExtract(ExtractedDataResult $result): void
    {

    }

}
