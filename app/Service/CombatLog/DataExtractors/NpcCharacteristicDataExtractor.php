<?php

namespace App\Service\CombatLog\DataExtractors;

use App;
use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatEvents\CombatLogEvent;
use App\Logic\CombatLog\CombatEvents\Prefixes\Spell;
use App\Logic\CombatLog\CombatEvents\Suffixes\AuraApplied\AuraAppliedInterface;
use App\Logic\CombatLog\Guid\Creature;
use App\Models\Characteristic;
use App\Models\CombatLog\CombatLogNpcCharacteristicObservation;
use App\Models\CombatLog\CombatLogNpcEvent;
use App\Models\CombatLog\CombatLogNpcEventType;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcCharacteristic;
use App\Models\Spell\Spell as SpellModel;
use App\Repositories\Interfaces\SpellRepositoryInterface;
use App\Service\CombatLog\DataExtractors\Logging\NpcCharacteristicDataExtractorLoggingInterface;
use App\Service\CombatLog\Dtos\DataExtraction\DataExtractionCurrentDungeon;
use App\Service\CombatLog\Dtos\DataExtraction\ExtractedDataResult;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class NpcCharacteristicDataExtractor implements DataExtractorInterface
{
    /** @var Collection<int, Npc|false> */
    private Collection $npcCache;

    /** @var Collection<string> */
    private Collection $addedCharacteristics;

    /**
     * All (npc_id, characteristic_id) pairs observed this session — batch-upserted in afterExtract.
     *
     * @var Collection<int, array{npc_id: int, characteristic_id: int}>
     */
    private Collection $pendingObservations;

    /**
     * Newly discovered pairs with no existing NpcCharacteristic — written in afterExtract.
     *
     * @var Collection<int, array{npc_id: int, characteristic_id: int}>
     */
    private Collection $pendingNewNpcCharacteristics;

    /** @var Collection<int, SpellModel> */
    private readonly Collection $spellsWithCharacteristics;

    private readonly NpcCharacteristicDataExtractorLoggingInterface $log;

    private ?string $currentCombatLogFilePath = null;

    public function __construct(
        private readonly SpellRepositoryInterface $spellRepository,
    ) {
        $this->npcCache                     = collect();
        $this->addedCharacteristics         = collect();
        $this->pendingObservations          = collect();
        $this->pendingNewNpcCharacteristics = collect();
        $this->spellsWithCharacteristics    = $this->spellRepository->getAllWithCharacteristic();

        $log = App::make(NpcCharacteristicDataExtractorLoggingInterface::class);
        /** @var NpcCharacteristicDataExtractorLoggingInterface $log */

        $this->log = $log;
    }

    public function beforeExtract(ExtractedDataResult $result, string $combatLogFilePath): void
    {
        $this->currentCombatLogFilePath = $combatLogFilePath;
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

        $this->addedCharacteristics->push($dedupKey);

        // Always queue an observation — this keeps the rolling window alive even for already-known characteristics
        $this->pendingObservations->push([
            'npc_id'            => $npcId,
            'characteristic_id' => $characteristicId,
        ]);

        $alreadyHasCharacteristic = $npc->npcCharacteristics->contains('characteristic_id', $characteristicId);

        if ($alreadyHasCharacteristic) {
            $this->log->extractDataCharacteristicAlreadyAssigned($npcId, $characteristicKey);
        } else {
            $this->pendingNewNpcCharacteristics->push([
                'npc_id'            => $npcId,
                'characteristic_id' => $characteristicId,
            ]);

            $this->log->extractDataAssignedCharacteristicToNpc($npcId, $characteristicKey, $parsedEvent->getRawEvent());
        }
    }

    public function afterExtract(ExtractedDataResult $result, string $combatLogFilePath): void
    {
        if ($this->pendingObservations->isNotEmpty()) {
            $now  = Carbon::now()->toDateTimeString();
            $rows = $this->pendingObservations->map(fn(array $obs) => [
                'npc_id'            => $obs['npc_id'],
                'characteristic_id' => $obs['characteristic_id'],
                'observed_on'       => Carbon::today()->toDateString(),
                'combat_log_path'   => $this->currentCombatLogFilePath ?? '',
                'created_at'        => $now,
                'updated_at'        => $now,
            ])->all();

            CombatLogNpcCharacteristicObservation::upsert(
                $rows,
                ['npc_id', 'characteristic_id', 'observed_on'],
                ['combat_log_path', 'updated_at'],
            );
        }

        foreach ($this->pendingNewNpcCharacteristics as $pending) {
            $npcCharacteristic = NpcCharacteristic::firstOrCreate([
                'npc_id'            => $pending['npc_id'],
                'characteristic_id' => $pending['characteristic_id'],
            ]);

            if ($npcCharacteristic->wasRecentlyCreated) {
                CombatLogNpcEvent::create([
                    'npc_id'          => $pending['npc_id'],
                    'event_type'      => CombatLogNpcEventType::CharacteristicAdded,
                    'model_class'     => Characteristic::class,
                    'model_id'        => $pending['characteristic_id'],
                    'combat_log_path' => $this->currentCombatLogFilePath,
                ]);

                $result->createdNpcCharacteristic();
            }
        }

        $this->npcCache                     = collect();
        $this->addedCharacteristics         = collect();
        $this->pendingObservations          = collect();
        $this->pendingNewNpcCharacteristics = collect();
        $this->currentCombatLogFilePath     = null;
    }
}
