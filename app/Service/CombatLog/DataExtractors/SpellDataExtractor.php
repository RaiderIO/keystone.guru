<?php

namespace App\Service\CombatLog\DataExtractors;

use App;
use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatEvents\CombatLogEvent;
use App\Logic\CombatLog\CombatEvents\Prefixes\Spell;
use App\Logic\CombatLog\CombatEvents\Suffixes\AuraApplied;
use App\Logic\CombatLog\CombatEvents\Suffixes\AuraBase;
use App\Logic\CombatLog\CombatEvents\Suffixes\AuraBroken;
use App\Logic\CombatLog\CombatEvents\Suffixes\AuraBrokenSpell;
use App\Logic\CombatLog\CombatEvents\Suffixes\Missed;
use App\Logic\CombatLog\CombatEvents\Suffixes\Summon;
use App\Logic\CombatLog\Guid\Creature;
use App\Logic\CombatLog\Guid\Player;
use App\Models\CombatLog\CombatLogNpcSpellAssignment;
use App\Models\CombatLog\CombatLogSpellUpdate;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcSpell;
use App\Models\Spell\Spell as SpellModel;
use App\Models\Spell\SpellDungeon;
use App\Service\CombatLog\DataExtractors\Logging\SpellDataExtractorLoggingInterface;
use App\Service\CombatLog\Dtos\DataExtraction\DataExtractionCurrentDungeon;
use App\Service\CombatLog\Dtos\DataExtraction\ExtractedDataResult;
use App\Service\Wowhead\WowheadServiceInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SpellDataExtractor implements DataExtractorInterface
{
    /** @var Collection<int, CombatLogEvent> */
    private Collection $spellIdsForDungeon;

    /** @var Collection<int> */
    private Collection $summonedNpcs;

    /** @var Collection<Npc> */
    private Collection $npcCache;

    /** @var Collection<int, SpellModel> */
    private Collection $allSpells;

    private SpellDataExtractorLoggingInterface $log;

    private ?string $currentCombatLogFilePath = null;

    public function __construct(
        private readonly WowheadServiceInterface $wowheadService
    ) {
        $this->spellIdsForDungeon = collect();
        $this->summonedNpcs       = collect();
        $this->npcCache           = collect();
        $this->allSpells          = SpellModel::all()->keyBy('id');

        $log = App::make(SpellDataExtractorLoggingInterface::class);
        /** @var SpellDataExtractorLoggingInterface $log */

        $this->log = $log;
    }

    public function beforeExtract(ExtractedDataResult $result, string $combatLogFilePath): void
    {
        $this->currentCombatLogFilePath = $combatLogFilePath;
    }

    public function extractData(ExtractedDataResult $result, DataExtractionCurrentDungeon $currentDungeon, BaseEvent $parsedEvent): void
    {
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
            (($destIsNpc && $suffix instanceof AuraApplied && $suffix->getAuraType() === AuraBase::AURA_TYPE_BUFF) ||
                $destGuid instanceof Player)) {


            // 8/2/2024 16:37:04.342-4  SPELL_AURA_BROKEN_SPELL,Creature-0-2085-2290-22770-171772-00002D40C5,"Mistveil Defender",0xa48,0x0,Player-4184-005B8B04,"Gulagcool-TheseGoToEleven-TR",0x512,0x0,1784,"Stealth",0x1,457129,"Deathstalker's Mark",1,DEBUFF
            // If the NPC broke an aura - that's not the NPC casting "Stealth" on a player - no it broke it out of it,
            // so don't assign that spell to this NPC
            if (!($suffix instanceof AuraBrokenSpell) && !($suffix instanceof AuraBroken)) {
                $this->extractSpellData($result, $parsedEvent, $prefix);

                $this->assignDungeonToSpell($result, $currentDungeon, $parsedEvent, $prefix);

                $this->assignSpellToNpc($result, $parsedEvent, $sourceGuid, $prefix);
            }
        }
    }

    public function afterExtract(ExtractedDataResult $result, string $combatLogFilePath): void
    {
        // Reset
        $this->spellIdsForDungeon       = collect();
        $this->summonedNpcs             = collect();
        $this->npcCache                 = collect();
        $this->currentCombatLogFilePath = null;
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
                        $parsedEvent->getGenericData()->getDestName()
                    );
                }
            }

            $result = true;
        }

        return $result;
    }

    private function extractSpellData(
        ExtractedDataResult $result,
        CombatLogEvent      $parsedEvent,
        Spell               $spell
    ): void {
        // Now that we've extract spell data from the combat log, either update the data that's there in the database,
        // or fetch some additional info from Wowhead
        $spellId = $spell->getSpellId();
        if ($this->allSpells->has($spellId)) {
            // Update the spell based on a found combat log event
            $this->updateSpell($result, $this->allSpells->get($spellId), $parsedEvent);

            return;
        }

        // Create a new spell and fetch the info for it
        $createdSpell = $this->createSpellAndFetchInfo($result, $this->wowheadService, $spell, $parsedEvent);
        if ($createdSpell instanceof SpellModel) {
            // Add to master list so that it doesn't get inserted twice
            $this->allSpells->put($spellId, $createdSpell);

            $this->log->createMissingSpellCreatedSpell($createdSpell->name, $spellId);
        }
    }

    private function assignDungeonToSpell(
        ExtractedDataResult          $result,
        DataExtractionCurrentDungeon $currentDungeon,
        CombatLogEvent               $parsedEvent,
        Spell                        $prefix
    ): void {
        if (!$this->spellIdsForDungeon->has($currentDungeon->dungeon->id)) {
            $this->spellIdsForDungeon->put($currentDungeon->dungeon->id, collect());
        }

        /** @var Collection $spellIdsForDungeon */
        $spellIdsForDungeon = $this->spellIdsForDungeon->get($currentDungeon->dungeon->id);

        if (!$spellIdsForDungeon->has($prefix->getSpellId())) {
            $spellIdsForDungeon->put($prefix->getSpellId(), $parsedEvent);

            $spell = SpellModel::find($prefix->getSpellId());
            if ($spell !== null) {
                // If this dungeon wasn't assigned to the spell yet..
                if (!$spell->spellDungeons()
                    ->where('dungeon_id', $currentDungeon->dungeon->id)
                    ->exists()) {

                    // Assign it
                    SpellDungeon::create([
                        'spell_id'   => $spell->id,
                        'dungeon_id' => $currentDungeon->dungeon->id,
                    ]);

                    $result->createdSpellDungeon();

                    $this->log->assignDungeonToSpellAssignedDungeonToSpell($prefix->getSpellId(), $currentDungeon->dungeon->id);
                }
            }
        }
    }

    private function assignSpellToNpc(
        ExtractedDataResult $result,
        CombatLogEvent      $parsedEvent,
        Creature            $sourceGuid,
        Spell               $prefix
    ): void {
        // Assign spell IDs to NPCs
        /** @var Npc|null|boolean $npc */
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
            $npcHasSpell = $npc->npcSpells->filter(function (NpcSpell $npcSpell) use ($prefix) {
                return $npcSpell->spell_id === $prefix->getSpellId();
            })->isNotEmpty();

            // This NPC now casts this spell - we have proof
            if (!$npcHasSpell) {
                NpcSpell::create([
                    'npc_id'   => $npc->id,
                    'spell_id' => $prefix->getSpellId(),
                ]);

                CombatLogNpcSpellAssignment::create([
                    'npc_id'          => $npc->id,
                    'spell_id'        => $prefix->getSpellId(),
                    'combat_log_path' => $this->currentCombatLogFilePath,
                    'raw_event'       => $parsedEvent->getRawEvent(),
                ]);

                // Refresh the relation
                $npc->load('npcSpells');

                $result->createdNpcSpell();

                $this->log->extractDataAssignedSpellToNpc($npc->id, $prefix->getSpellId(), $parsedEvent->getRawEvent());
            }
        } else {
            $this->log->extractDataSpellNpcNull($sourceGuid->getId());
        }
    }

    private function updateSpell(
        ExtractedDataResult $result,
        SpellModel          $spell,
        CombatLogEvent      $combatLogEvent
    ): bool {
        $before = $spell->getAttributes();

        $suffix        = $combatLogEvent->getSuffix();
        $spell->aura   = $spell->aura ||
        ($suffix instanceof AuraApplied && $suffix->getAuraType() === AuraBase::AURA_TYPE_BUFF &&
            $combatLogEvent->getGenericData()->getDestGuid() instanceof Creature &&
            $combatLogEvent->getGenericData()->getDestGuid()->getUnitType() === Creature::CREATURE_UNIT_TYPE_CREATURE) ? 1 : 0;
        $spell->debuff = $spell->debuff ||
        ($suffix instanceof AuraApplied && $suffix->getAuraType() === AuraBase::AURA_TYPE_DEBUFF &&
            $combatLogEvent->getGenericData()->getDestGuid() instanceof Player) ? 1 : 0;
        // If a spell was missed somehow, write it to the miss_types_mask field
        if ($suffix instanceof Missed) {
            $spell->miss_types_mask |=
                SpellModel::ALL_MISS_TYPES[ucfirst(strtolower($suffix->getMissType()))] ?? 0;
        }

        if ($spell->isDirty() && $spell->save()) {
            $boolToInt = fn($value) => is_bool($value) ? (int)$value : $value;
            CombatLogSpellUpdate::create([
                'spell_id'        => $spell->id,
                'before'          => json_encode(array_map($boolToInt, $before)),
                'after'           => json_encode(array_map($boolToInt, $spell->getAttributes())),
                'combat_log_path' => $this->currentCombatLogFilePath,
                'raw_event'       => $combatLogEvent->getRawEvent(),
            ]);

            $result->updatedSpell();

            return true;
        }

        return false;
    }

    private function createSpellAndFetchInfo(
        ExtractedDataResult     $result,
        WowheadServiceInterface $wowheadService,
        Spell                   $spell,
        CombatLogEvent          $combatLogEvent
    ): ?SpellModel {
        try {
            $this->log->createSpellAndFetchInfoStart($spell->getSpellId());

            $spellDataResult = $wowheadService->getSpellData($spell->getSpellId());

            $spellAttributes = [
                'id'              => $spell->getSpellId(),
                'fetched_data_at' => Carbon::now(),
            ];

            if ($spellDataResult === null) {
                $this->log->createSpellAndFetchInfoSpellDataResultIsNull($spell->getSpellId());

                $spellAttributes = array_merge($spellAttributes, [
                    'dispel_type'  => '',
                    'icon_name'    => '',
                    'name'         => $spell->getSpellName(),
                    'schools_mask' => $spell->getSpellSchool(),
                    'aura'         => false,
                ]);
            } else {
                $spellAttributes = array_merge($spellAttributes, $spellDataResult->toArray());
            }

            $result->createdSpell();

            $createdSpell = SpellModel::create($spellAttributes);

            // Ensure we know this is when the spell was created
            CombatLogSpellUpdate::create([
                'spell_id'        => $createdSpell->id,
                'before'          => null,
                'after'           => json_encode($createdSpell->getAttributes()),
                'combat_log_path' => $this->currentCombatLogFilePath,
                'raw_event'       => $combatLogEvent->getRawEvent(),
            ]);

            // With the created spell, update it according to the combat log event that created it (aura assignments etc)
            $this->updateSpell($result, $createdSpell, $combatLogEvent);

            return $createdSpell;
        } finally {
            $this->log->createSpellAndFetchInfoEnd();
        }
    }
}
