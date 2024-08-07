<?php

namespace App\Service\CombatLog\DataExtractors;

use App;
use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatEvents\CombatLogEvent;
use App\Logic\CombatLog\CombatEvents\Prefixes\Spell;
use App\Logic\CombatLog\CombatEvents\Suffixes\AuraApplied;
use App\Logic\CombatLog\CombatEvents\Suffixes\Summon;
use App\Logic\CombatLog\Guid\Creature;
use App\Logic\CombatLog\Guid\Player;
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

    /** @var Collection<int, SpellModel> */
    private Collection $allSpells;

    private SpellDataExtractorLoggingInterface $log;

    public function __construct(
        private WowheadServiceInterface $wowheadService
    ) {
        $this->spellIdsForDungeon = collect();
        $this->summonedNpcs       = collect();
        $this->allSpells          = SpellModel::all()->keyBy('id');

        $log = App::make(SpellDataExtractorLoggingInterface::class);
        /** @var SpellDataExtractorLoggingInterface $log */

        $this->log = $log;
    }

    public function beforeExtract(ExtractedDataResult $result): void
    {

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

        $suffix = $parsedEvent->getSuffix();
        if ($suffix instanceof AuraApplied) {
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
                // If destination is an NPC and it's a buff, or if the target was a player
                (($destIsNpc && $suffix->getAuraType() === AuraApplied::AURA_TYPE_BUFF) ||
                    $destGuid instanceof Player)) {

                $this->createMissingSpell($result, $parsedEvent, $prefix);

                $this->assignDungeonToSpell($result, $currentDungeon, $parsedEvent, $prefix);

                $this->assignSpellToNpc($result, $parsedEvent, $sourceGuid, $prefix);
            }
        }
    }

    public function afterExtract(ExtractedDataResult $result): void
    {
        // Reset
        $this->spellIdsForDungeon = collect();
        $this->summonedNpcs       = collect();
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

    private function createMissingSpell(
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
        /** @var Npc $npc */
        $npc = Npc::with('npcSpells')->find($sourceGuid->getId());
        if ($npc !== null) {
            // use ->filter()
            $npcHasSpell = $npc->npcSpells->filter(function (NpcSpell $npcSpell) use ($prefix) {
                return $npcSpell->spell_id === $prefix->getSpellId();
            })->isNotEmpty();

            // This NPC now casts this spell - we have proof
            if (!$npcHasSpell) {
                NpcSpell::create([
                    'npc_id'   => $npc->id,
                    'spell_id' => $prefix->getSpellId(),
                ]);

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
        /** @var AuraApplied $suffix */
        $suffix        = $combatLogEvent->getSuffix();
        $spell->aura   = $spell->aura ||
            ($suffix->getAuraType() === AuraApplied::AURA_TYPE_BUFF &&
                $combatLogEvent->getGenericData()->getDestGuid() instanceof Creature &&
                $combatLogEvent->getGenericData()->getDestGuid()->getUnitType() === Creature::CREATURE_UNIT_TYPE_CREATURE);
        $spell->debuff = $spell->debuff ||
            ($suffix->getAuraType() === AuraApplied::AURA_TYPE_DEBUFF &&
                $combatLogEvent->getGenericData()->getDestGuid() instanceof Player);

        if ($spell->isDirty() && $spell->save()) {
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

            // With the created spell, update it according to the combat log event that created it (aura assignments etc)
            $this->updateSpell($result, $createdSpell, $combatLogEvent);

            return $createdSpell;
        } finally {
            $this->log->createSpellAndFetchInfoEnd();
        }
    }
}
