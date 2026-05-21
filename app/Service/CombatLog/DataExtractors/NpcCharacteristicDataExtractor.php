<?php

namespace App\Service\CombatLog\DataExtractors;

use App;
use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatEvents\CombatLogEvent;
use App\Logic\CombatLog\CombatEvents\Prefixes\Spell;
use App\Logic\CombatLog\CombatEvents\Suffixes\AuraApplied\AuraAppliedInterface;
use App\Logic\CombatLog\Guid\Creature;
use App\Models\Characteristic;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcCharacteristic;
use App\Models\Spell\Spell as SpellModel;
use App\Repositories\Interfaces\SpellRepositoryInterface;
use App\Service\CombatLog\DataExtractors\Logging\NpcCharacteristicDataExtractorLoggingInterface;
use App\Service\CombatLog\Dtos\DataExtraction\DataExtractionCurrentDungeon;
use App\Service\CombatLog\Dtos\DataExtraction\ExtractedDataResult;
use Illuminate\Support\Collection;

class NpcCharacteristicDataExtractor implements DataExtractorInterface
{
    /** @var Collection<int, Npc|false> */
    private Collection $npcCache;

    /** @var Collection<string> */
    private Collection $addedCharacteristics;

    /** @var Collection<int, SpellModel> */
    private readonly Collection $spellsWithCharacteristics;

    private readonly NpcCharacteristicDataExtractorLoggingInterface $log;

    public function __construct(
        private readonly SpellRepositoryInterface $spellRepository,
    ) {
        $this->npcCache                  = collect();
        $this->addedCharacteristics      = collect();
        $this->spellsWithCharacteristics = $this->spellRepository->getAllWithCharacteristic();

        $log = App::make(NpcCharacteristicDataExtractorLoggingInterface::class);
        /** @var NpcCharacteristicDataExtractorLoggingInterface $log */

        $this->log = $log;
    }

    public function beforeExtract(ExtractedDataResult $result, string $combatLogFilePath): void
    {
    }

    public function extractData(
        ExtractedDataResult          $result,
        DataExtractionCurrentDungeon $currentDungeon,
        BaseEvent                    $parsedEvent,
    ): void {
        if (!($parsedEvent instanceof CombatLogEvent)) {
            return;
        }

        $prefix = $parsedEvent->getPrefix();
        if (!($prefix instanceof Spell)) {
            return;
        }

        if (!($parsedEvent->getSuffix() instanceof AuraAppliedInterface)) {
            return;
        }

        $destGuid = $parsedEvent->getGenericData()->getDestGuid();
        if (!($destGuid instanceof Creature) ||
            $destGuid->getUnitType() !== Creature::CREATURE_UNIT_TYPE_CREATURE) {
            return;
        }

        $spellId = $prefix->getSpellId();
        /** @var SpellModel|null $spell */
        $spell = $this->spellsWithCharacteristics->get($spellId);
        if ($spell === null) {
            return;
        }

        $characteristicId  = $spell->characteristic_id;
        $characteristicKey = array_flip(Characteristic::ALL)[$characteristicId] ?? (string)$characteristicId;
        $npcId             = $destGuid->getId();

        /** @var Npc|null|false $npc */
        $npc = $this->npcCache->get($npcId);
        if ($npc === false) {
            return;
        }

        if ($npc === null) {
            $npc = Npc::with('npcCharacteristics')->find($npcId);
            $this->npcCache->put($npcId, $npc ?? false);
        }

        if (!($npc instanceof Npc)) {
            $this->log->extractDataNpcNotFound($npcId);

            return;
        }

        $dedupKey = sprintf('%d-%d', $npcId, $characteristicId);
        if ($this->addedCharacteristics->contains($dedupKey)) {
            $this->log->extractDataCharacteristicAlreadyAssigned($npcId, $characteristicKey);

            return;
        }

        $alreadyHasCharacteristic = $npc->npcCharacteristics
            ->contains('characteristic_id', $characteristicId);

        if ($alreadyHasCharacteristic) {
            $this->addedCharacteristics->push($dedupKey);
            $this->log->extractDataCharacteristicAlreadyAssigned($npcId, $characteristicKey);

            return;
        }

        NpcCharacteristic::create([
            'npc_id'            => $npcId,
            'characteristic_id' => $characteristicId,
        ]);

        $npc->unsetRelation('npcCharacteristics')->load('npcCharacteristics');
        $this->addedCharacteristics->push($dedupKey);

        $result->createdNpcCharacteristic();

        $this->log->extractDataAssignedCharacteristicToNpc($npcId, $characteristicKey, $parsedEvent->getRawEvent());
    }

    public function afterExtract(ExtractedDataResult $result, string $combatLogFilePath): void
    {
        $this->npcCache             = collect();
        $this->addedCharacteristics = collect();
    }
}
