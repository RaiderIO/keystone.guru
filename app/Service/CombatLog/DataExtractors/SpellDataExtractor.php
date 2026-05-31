<?php

namespace App\Service\CombatLog\DataExtractors;

use App;
use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatEvents\CombatLogEvent;
use App\Logic\CombatLog\CombatEvents\Prefixes\Spell;
use App\Logic\CombatLog\CombatEvents\Suffixes\AuraApplied\AuraAppliedInterface;
use App\Logic\CombatLog\CombatEvents\Suffixes\AuraBase;
use App\Logic\CombatLog\CombatEvents\Suffixes\AuraBroken;
use App\Logic\CombatLog\CombatEvents\Suffixes\AuraBrokenSpell;
use App\Logic\CombatLog\CombatEvents\Suffixes\Interrupt;
use App\Logic\CombatLog\CombatEvents\Suffixes\Missed\MissedInterface;
use App\Logic\CombatLog\CombatEvents\Suffixes\Summon;
use App\Logic\CombatLog\Guid\Creature;
use App\Logic\CombatLog\Guid\Player;
use App\Models\CombatLog\CombatLogNpcEvent;
use App\Models\CombatLog\CombatLogNpcEventType;
use App\Models\CombatLog\CombatLogSpellEvent;
use App\Models\CombatLog\CombatLogSpellEventType;
use App\Models\CombatLog\CombatLogSpellPropertyObservation;
use App\Models\CombatLog\SpellProperty;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcSpell;
use App\Models\Spell\Spell as SpellModel;
use App\Models\Spell\SpellDungeon;
use App\Service\CombatLog\DataExtractors\Logging\SpellDataExtractorLoggingInterface;
use App\Service\CombatLog\Dtos\DataExtraction\DataExtractionCurrentDungeon;
use App\Service\CombatLog\Dtos\DataExtraction\ExtractedDataResult;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SpellDataExtractor implements DataExtractorInterface
{
    /** @var Collection<int, Collection<CombatLogEvent>> */
    private Collection $spellIdsForDungeon;

    /** @var Collection<int> */
    private Collection $summonedNpcs;

    /** @var Collection<int, Npc|false> */
    private Collection $npcCache;

    /** @var Collection<int, SpellModel> */
    private readonly Collection $allSpells;

    /**
     * All (spell_id, property) pairs observed this session — batch-upserted in afterExtract.
     *
     * @var Collection<string, array{spell_id: int, property: SpellProperty}>
     */
    private Collection $pendingPropertyObservations;

    /**
     * New (npc_id, spell_id, dungeon_id) triples discovered this session — written in afterExtract.
     *
     * @var Collection<string, array{npc_id: int, spell_id: int, dungeon_id: int}>
     */
    private Collection $pendingNpcSpellAssignments;

    /**
     * Newly created spells — SpellCreated events written in afterExtract.
     *
     * @var Collection<int, SpellModel>
     */
    private Collection $pendingNewSpells;

    private readonly SpellDataExtractorLoggingInterface $log;

    private ?string $currentCombatLogFilePath = null;

    public function __construct()
    {
        $this->spellIdsForDungeon          = collect();
        $this->summonedNpcs                = collect();
        $this->npcCache                    = collect();
        $this->pendingPropertyObservations = collect();
        $this->pendingNpcSpellAssignments  = collect();
        $this->pendingNewSpells            = collect();
        $this->allSpells                   = SpellModel::with('spellDungeons')->get()->keyBy('id');

        $log = App::make(SpellDataExtractorLoggingInterface::class);
        /** @var SpellDataExtractorLoggingInterface $log */

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
            // Don't log anything because that'd just spam the hell out of it
            return;
        }

        $prefix = $parsedEvent->getPrefix();
        if (!($prefix instanceof Spell)) {
            return;
        }

        // Ignore summoned enemies - don't add spells to them!
        if ($this->isSummonedNpc($parsedEvent)) {
            return;
        }

        $suffix      = $parsedEvent->getSuffix();
        $sourceGuid  = $parsedEvent->getGenericData()->getSourceGuid();
        $sourceIsNpc = $sourceGuid instanceof Creature &&
            // Only actual creatures - not pets
            $sourceGuid->getUnitType() === Creature::CREATURE_UNIT_TYPE_CREATURE;

        $destGuid  = $parsedEvent->getGenericData()->getDestGuid();
        $destIsNpc = $destGuid instanceof Creature &&
            // Only actual creatures - not pets
            $destGuid->getUnitType() === Creature::CREATURE_UNIT_TYPE_CREATURE;

        // Npcs can cast buffs on one another, and it'll count for a spell that they cast
        // Some player spells cause NPCs to cast spells on other NPCs, but those will be debuffs
        // (such as Death Knight Blinding Sleet), we don't want to attribute that to the NPC
        // 8/2/2024 16:24:18.477-4  SPELL_AURA_APPLIED,Creature-0-2085-2290-22744-164921-00012D4051,"Drust Harvester",0xa48,0x0,Creature-0-2085-2290-22744-164921-00012D4051,"Drust Harvester",0xa48,0x0,317898,"Blinding Sleet",0x10,DEBUFF
        if ($sourceIsNpc &&
            // Ignore summoned NPCs
            $this->summonedNpcs->search($sourceGuid->getId()) === false &&
            // If destination is an NPC, and it's a buff, or if the target was a player
            (($destIsNpc && $suffix instanceof AuraAppliedInterface && $suffix->getAuraType() === AuraBase::AURA_TYPE_BUFF) ||
                $destGuid instanceof Player)) {
            // 8/2/2024 16:37:04.342-4  SPELL_AURA_BROKEN_SPELL,Creature-0-2085-2290-22770-171772-00002D40C5,"Mistveil Defender",0xa48,0x0,Player-4184-005B8B04,"Gulagcool-TheseGoToEleven-TR",0x512,0x0,1784,"Stealth",0x1,457129,"Deathstalker's Mark",1,DEBUFF
            // If the NPC broke an aura - that's not the NPC casting "Stealth" on a player - no it broke it out of it,
            // so don't assign that spell to this NPC
            if (!($suffix instanceof AuraBrokenSpell) && !($suffix instanceof AuraBroken)) {
                $this->extractSpellData($result, $prefix);
                $this->collectPropertyObservations($parsedEvent, $prefix);
                $this->assignDungeonToSpell($result, $currentDungeon, $parsedEvent, $prefix);
                $this->assignSpellToNpc($result, $currentDungeon, $parsedEvent, $sourceGuid, $prefix);
            }
        }

        // Track interrupted NPC spells: when a player interrupts a creature, mark the interrupted spell as interruptible.
        if ($suffix instanceof Interrupt &&
            $sourceGuid instanceof Player &&
            $destGuid instanceof Creature &&
            $destGuid->getUnitType() === Creature::CREATURE_UNIT_TYPE_CREATURE &&
            $this->summonedNpcs->search($destGuid->getId()) === false) {
            $this->collectInterruptObservation($result, $suffix);
        }
    }

    public function afterExtract(ExtractedDataResult $result, string $combatLogFilePath): void
    {
        // SpellCreated events must be written before PropertyChanged so the event feed is chronologically correct
        foreach ($this->pendingNewSpells as $spellId => $spell) {
            CombatLogSpellEvent::create([
                'spell_id'        => $spellId,
                'event_type'      => CombatLogSpellEventType::SpellCreated,
                'property'        => null,
                'combat_log_path' => $this->currentCombatLogFilePath,
            ]);
        }

        if ($this->pendingPropertyObservations->isNotEmpty()) {
            $now  = Carbon::now()->toDateTimeString();
            $rows = $this->pendingPropertyObservations->map(fn(array $observation) => [
                'spell_id'        => $observation['spell_id'],
                'property'        => $observation['property']->value,
                'observed_on'     => Carbon::today()->toDateString(),
                'combat_log_path' => $this->currentCombatLogFilePath ?? '',
                'created_at'      => $now,
                'updated_at'      => $now,
            ])->all();

            CombatLogSpellPropertyObservation::upsert(
                $rows,
                ['spell_id', 'property', 'observed_on'],
                ['combat_log_path', 'updated_at'],
            );

            foreach ($this->pendingPropertyObservations as $observation) {
                /** @var SpellModel|null $spell */
                $spell = $this->allSpells->get($observation['spell_id']);
                if ($spell === null || $this->spellHasProperty($spell, $observation['property'])) {
                    continue;
                }

                $this->applyPropertyToSpell($spell, $observation['property']);

                if ($spell->isDirty() && $spell->save()) {
                    $result->updatedSpell();

                    CombatLogSpellEvent::create([
                        'spell_id'        => $observation['spell_id'],
                        'event_type'      => CombatLogSpellEventType::PropertyChanged,
                        'property'        => $observation['property'],
                        'combat_log_path' => $this->currentCombatLogFilePath,
                    ]);
                }
            }
        }

        foreach ($this->pendingNpcSpellAssignments as $pending) {
            NpcSpell::create([
                'npc_id'   => $pending['npc_id'],
                'spell_id' => $pending['spell_id'],
            ]);

            if (!SpellDungeon::where('spell_id', $pending['spell_id'])
                ->where('dungeon_id', $pending['dungeon_id'])->exists()) {
                SpellDungeon::create([
                    'spell_id'   => $pending['spell_id'],
                    'dungeon_id' => $pending['dungeon_id'],
                ]);
            }

            CombatLogNpcEvent::create([
                'npc_id'          => $pending['npc_id'],
                'event_type'      => CombatLogNpcEventType::SpellAssigned,
                'model_class'     => SpellModel::class,
                'model_id'        => $pending['spell_id'],
                'combat_log_path' => $this->currentCombatLogFilePath,
            ]);

            $result->createdNpcSpell();
        }

        $this->spellIdsForDungeon          = collect();
        $this->summonedNpcs                = collect();
        $this->npcCache                    = collect();
        $this->pendingPropertyObservations = collect();
        $this->pendingNpcSpellAssignments  = collect();
        $this->pendingNewSpells            = collect();
        $this->currentCombatLogFilePath    = null;
    }

    private function isSummonedNpc(CombatLogEvent $parsedEvent): bool
    {
        $result = false;
        if ($parsedEvent->getSuffix() instanceof Summon) {
            $guid = $parsedEvent->getGenericData()->getDestGuid();

            $npcId = $guid instanceof Creature ? $guid->getId() : null;
            if ($npcId !== null) {
                if ($this->summonedNpcs->search($npcId) === false) {
                    $this->summonedNpcs->push($npcId);

                    $this->log->isSummonedNpcNpcWasSummoned(
                        $npcId,
                        $parsedEvent->getGenericData()->getDestName(),
                    );
                }
            }

            $result = true;
        }

        return $result;
    }

    private function extractSpellData(ExtractedDataResult $result, Spell $spell): void
    {
        $spellId = $spell->getSpellId();
        if ($this->allSpells->has($spellId)) {
            return;
        }

        $createdSpell = $this->createSpell($result, $spell);
        $this->allSpells->put($spellId, $createdSpell);
        $this->pendingNewSpells->put($spellId, $createdSpell);

        $this->log->createMissingSpellCreatedSpell($createdSpell->name, $spellId);
    }

    private function collectPropertyObservations(CombatLogEvent $parsedEvent, Spell $prefix): void
    {
        $spellId  = $prefix->getSpellId();
        $suffix   = $parsedEvent->getSuffix();
        $destGuid = $parsedEvent->getGenericData()->getDestGuid();

        $properties = [];

        if ($suffix instanceof AuraAppliedInterface) {
            if ($suffix->getAuraType() === AuraBase::AURA_TYPE_BUFF &&
                $destGuid instanceof Creature &&
                $destGuid->getUnitType() === Creature::CREATURE_UNIT_TYPE_CREATURE) {
                $properties[] = SpellProperty::Aura;
            } elseif ($suffix->getAuraType() === AuraBase::AURA_TYPE_DEBUFF && $destGuid instanceof Player) {
                $properties[] = SpellProperty::Debuff;
            }
        }

        if ($suffix instanceof MissedInterface) {
            $bit = SpellModel::GUID_MISS_TYPE_MAPPING[$suffix->getMissType()::class] ?? null;
            if ($bit !== null) {
                $properties[] = SpellProperty::fromMissTypeBit($bit);
            }
        }

        foreach ($properties as $property) {
            $dedupKey = sprintf('%d-%s', $spellId, $property->value);
            if (!$this->pendingPropertyObservations->has($dedupKey)) {
                $this->pendingPropertyObservations->put($dedupKey, [
                    'spell_id' => $spellId,
                    'property' => $property,
                ]);
            }
        }
    }

    private function assignDungeonToSpell(
        ExtractedDataResult          $result,
        DataExtractionCurrentDungeon $currentDungeon,
        CombatLogEvent               $parsedEvent,
        Spell                        $prefix,
    ): void {
        if (!$this->spellIdsForDungeon->has($currentDungeon->dungeon->id)) {
            $this->spellIdsForDungeon->put($currentDungeon->dungeon->id, collect());
        }

        /** @var Collection<int, CombatLogEvent> $spellIdsForDungeon */
        $spellIdsForDungeon = $this->spellIdsForDungeon->get($currentDungeon->dungeon->id);

        if (!$spellIdsForDungeon->has($prefix->getSpellId())) {
            $spellIdsForDungeon->put($prefix->getSpellId(), $parsedEvent);

            $spell = $this->allSpells->get($prefix->getSpellId());

            // Only assign spells that are NOT player spells!
            if (
                $spell !== null &&
                $spell->category === sprintf('spellcategory.%s', SpellModel::CATEGORY_UNKNOWN)
            ) {
                // If this dungeon wasn't assigned to the spell yet..
                if ($spell->spellDungeons
                    ->where('dungeon_id', $currentDungeon->dungeon->id)
                    ->isEmpty()) {
                    // Assign it
                    SpellDungeon::create([
                        'spell_id'   => $spell->id,
                        'dungeon_id' => $currentDungeon->dungeon->id,
                    ]);

                    $spell->unsetRelation('spellDungeons')->load('spellDungeons');

                    $result->createdSpellDungeon();

                    $this->log->assignDungeonToSpellAssignedDungeonToSpell($prefix->getSpellId(), $currentDungeon->dungeon->id);
                }
            }
        }
    }

    private function assignSpellToNpc(
        ExtractedDataResult          $result,
        DataExtractionCurrentDungeon $currentDungeon,
        CombatLogEvent               $parsedEvent,
        Creature                     $sourceGuid,
        Spell                        $prefix,
    ): void {
        // Check if the spell can be assigned
        $spell = $this->allSpells->get($prefix->getSpellId());
        if ($spell === null || $spell->category !== sprintf('spellcategory.%s', SpellModel::CATEGORY_UNKNOWN)) {
            return;
        }

        // Assign spell IDs to NPCs
        /** @var Npc|null|false $npc */
        $npc = $this->npcCache->get($sourceGuid->getId());
        if ($npc === false) {
            return;
        }

        if ($npc === null) {
            $npc = Npc::with('npcSpells')->find($sourceGuid->getId());
            // If we couldn't find the NPC, just write false and we'll never try it again for this NPC
            $this->npcCache->put($sourceGuid->getId(), $npc ?? false);
        }

        if ($npc instanceof Npc) {
            $dedupKey    = sprintf('%d-%d', $npc->id, $prefix->getSpellId());
            $npcHasSpell = $npc->npcSpells->filter(fn(NpcSpell $npcSpell) => $npcSpell->spell_id === $prefix->getSpellId())->isNotEmpty();

            // This NPC now casts this spell - we have proof
            if (!$npcHasSpell && !$this->pendingNpcSpellAssignments->has($dedupKey)) {
                $this->pendingNpcSpellAssignments->put($dedupKey, [
                    'npc_id'     => $npc->id,
                    'spell_id'   => $prefix->getSpellId(),
                    'dungeon_id' => $currentDungeon->dungeon->id,
                ]);

                $this->log->extractDataAssignedSpellToNpc($npc->id, $prefix->getSpellId(), $parsedEvent->getRawEvent());
            }
        } else {
            $this->log->extractDataSpellNpcNull($sourceGuid->getId());
        }
    }

    private function collectInterruptObservation(ExtractedDataResult $result, Interrupt $interrupt): void
    {
        $spellId = $interrupt->getExtraSpellId();

        if (!$this->allSpells->has($spellId)) {
            $createdSpell = SpellModel::create([
                'id'           => $spellId,
                'dispel_type'  => '',
                'icon_name'    => '',
                'name'         => $interrupt->getExtraSpellName(),
                'schools_mask' => $interrupt->getExtraSchool(),
                'aura'         => false,
            ]);
            $createdSpell->setRelation('spellDungeons', collect());
            $result->createdSpell();
            $this->allSpells->put($spellId, $createdSpell);
            $this->pendingNewSpells->put($spellId, $createdSpell);

            $this->log->createMissingSpellCreatedSpell($createdSpell->name, $spellId);
        }

        $dedupKey = sprintf('%d-%s', $spellId, SpellProperty::MissInterrupt->value);
        if (!$this->pendingPropertyObservations->has($dedupKey)) {
            $this->pendingPropertyObservations->put($dedupKey, [
                'spell_id' => $spellId,
                'property' => SpellProperty::MissInterrupt,
            ]);
        }
    }

    private function applyPropertyToSpell(SpellModel $spell, SpellProperty $property): void
    {
        if ($property === SpellProperty::Aura) {
            $spell->aura = true;
        } elseif ($property === SpellProperty::Debuff) {
            $spell->debuff = true;
        } else {
            $spell->miss_types_mask |= $this->missTypeBit($property);
        }
    }

    private function spellHasProperty(SpellModel $spell, SpellProperty $property): bool
    {
        if ($property === SpellProperty::Aura) {
            return $spell->aura;
        }

        if ($property === SpellProperty::Debuff) {
            return (bool)$spell->debuff;
        }

        return (bool)($spell->miss_types_mask & $this->missTypeBit($property));
    }

    private function missTypeBit(SpellProperty $property): int
    {
        $name = substr($property->value, 5);
        $bit  = array_search($name, SpellModel::ALL_MISS_TYPES, true);

        return $bit !== false ? (int)$bit : 0;
    }

    private function createSpell(ExtractedDataResult $result, Spell $prefix): SpellModel
    {
        $createdSpell = SpellModel::create([
            'id'           => $prefix->getSpellId(),
            'dispel_type'  => '',
            'icon_name'    => '',
            'name'         => $prefix->getSpellName(),
            'schools_mask' => $prefix->getSpellSchool(),
            'aura'         => false,
        ]);
        $createdSpell->setRelation('spellDungeons', collect());

        $result->createdSpell();

        return $createdSpell;
    }
}
