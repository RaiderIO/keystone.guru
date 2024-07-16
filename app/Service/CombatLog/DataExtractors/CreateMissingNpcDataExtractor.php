<?php

namespace App\Service\CombatLog\DataExtractors;

use App;
use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\CombatEvents\CombatLogEvent;
use App\Logic\CombatLog\CombatEvents\Suffixes\Summon;
use App\Logic\CombatLog\Guid\Creature;
use App\Models\Affix;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcClass;
use App\Models\Npc\NpcClassification;
use App\Models\Npc\NpcType;
use App\Service\CombatLog\DataExtractors\Logging\CreateMissingNpcDataExtractorLoggingInterface;
use App\Service\CombatLog\Dtos\DataExtraction\DataExtractionCurrentDungeon;
use App\Service\CombatLog\Dtos\DataExtraction\ExtractedDataResult;
use Illuminate\Support\Collection;

class CreateMissingNpcDataExtractor implements DataExtractorInterface
{
    /** @var Collection<int>> */
    private Collection $checkedNpcIds;

    private CreateMissingNpcDataExtractorLoggingInterface $log;

    public function __construct()
    {
        $this->checkedNpcIds = collect();
        $log                 = App::make(CreateMissingNpcDataExtractorLoggingInterface::class);
        /** @var CreateMissingNpcDataExtractorLoggingInterface $log */

        $this->log = $log;
    }

    public function extractData(ExtractedDataResult $result, DataExtractionCurrentDungeon $currentDungeon, BaseEvent $parsedEvent): void
    {
        // Don't create summoned enemies!
        if ($parsedEvent instanceof CombatLogEvent && $parsedEvent->getSuffix() instanceof Summon) {
            $guid = $parsedEvent->getGenericData()->getDestGuid();

            $npcId = $guid instanceof Creature ? $guid->getId() : null;
            if ($npcId !== null) {
                if ($this->checkedNpcIds->search($npcId) === false) {
                    $this->checkedNpcIds->push($npcId);

                    $this->log->extractDataNpcWasSummoned(
                        $npcId,
                        $parsedEvent->getGenericData()->getDestName()
                    );
                }
            }

            return;
        }

        if (!($parsedEvent instanceof AdvancedCombatLogEvent)) {
            return;
        }
        $guid = $parsedEvent->getAdvancedData()->getInfoGuid();

        if ($guid instanceof Creature && $this->checkedNpcIds->search($guid->getId()) === false) {
            $this->checkedNpcIds->push($guid->getId());

            $npc = Npc::find($guid->getId());

            if ($npc !== null) {
                // Npc existed, we're not going to do anything else
                return;
            }

            // Determine name
            if ($parsedEvent->getGenericData()->getSourceGuid()?->getGuid() === $guid->getGuid()) {
                $name = $parsedEvent->getGenericData()->getSourceName();
            } else if ($parsedEvent->getGenericData()->getDestGuid()?->getGuid() === $guid->getGuid()) {
                $name = $parsedEvent->getGenericData()->getDestName();
            } else {
                $this->log->extractDataNpcNameNotFound(
                    $parsedEvent->getGenericData()->getSourceGuid()?->getGuid(),
                    $parsedEvent->getGenericData()->getDestGuid()?->getGuid()
                );

                return;
            }

            // Don't create any pets!
            if ($parsedEvent->getAdvancedData()->getOwnerGuid() !== null) {
                $this->log->extractDataNpcWasAPet($guid->getId(), $name);

                return;
            }

            // Determine health
            if ($currentDungeon->keyLevel === null) {
                $baseHealth = $parsedEvent->getAdvancedData()->getMaxHP();
            } else {
                // Calculate the base health based on the current key level + current max hp
                $baseHealth = (int)($parsedEvent->getAdvancedData()->getMaxHP() / $npc->getScalingFactor(
                        $currentDungeon->keyLevel,
                        $currentDungeon->affixGroup?->hasAffix(Affix::AFFIX_FORTIFIED) ?? false,
                        $currentDungeon->affixGroup?->hasAffix(Affix::AFFIX_TYRANNICAL) ?? false,
                        $currentDungeon->affixGroup?->hasAffix(Affix::AFFIX_THUNDERING) ?? false,
                    ));
            }

            $createdNpc = Npc::create([
                'id'                => $guid->getId(),
                'dungeon_id'        => $currentDungeon->dungeon->id,
                'classification_id' => NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_ELITE],
                'npc_type_id'       => NpcType::HUMANOID,
                'npc_class_id'      => NpcClass::ALL[NpcClass::NPC_CLASS_MELEE],
                'display_id'        => null,
                'name'              => $name,
                'base_health'       => $baseHealth,
                'health_percentage' => null,
                'aggressiveness'    => Npc::AGGRESSIVENESS_AGGRESSIVE,
                'dangerous'         => 0,
                'truesight'         => 0,
            ]);

            if ($createdNpc instanceof Npc) {
                $result->createdNpc();

                $this->log->extractDataCreatedNpc(
                    $guid->getId(),
                    $name,
                    $baseHealth,
                    $parsedEvent->getRawEvent(),
                );
            } else {
                $this->log->extractDataNpcNotCreated(
                    $guid->getId(),
                    $name,
                    $baseHealth
                );
            }
        }
    }

}
