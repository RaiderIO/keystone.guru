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
use App\Models\Dungeon;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcSpell;
use App\Models\Spell\Spell as SpellModel;
use App\Models\Spell\SpellDungeon;
use App\Service\CombatLog\DataExtractors\Logging\SpellDataExtractorLoggingInterface;
use App\Service\CombatLog\Dtos\DataExtraction\DataExtractionCurrentDungeon;
use App\Service\CombatLog\Dtos\DataExtraction\ExtractedDataResult;
use App\Service\Wowhead\WowheadServiceInterface;
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

        // Don't create summoned enemies!
        if ($parsedEvent instanceof CombatLogEvent && $parsedEvent->getSuffix() instanceof Summon) {
            $guid = $parsedEvent->getGenericData()->getDestGuid();

            $npcId = $guid instanceof Creature ? $guid->getId() : null;
            if ($npcId !== null) {
                if ($this->summonedNpcs->search($npcId) === false) {
                    $this->summonedNpcs->push($npcId);

                    $this->log->extractDataNpcWasSummoned(
                        $npcId,
                        $parsedEvent->getGenericData()->getDestName()
                    );
                }
            }

            return;
        }

        $suffix = $parsedEvent->getSuffix();
        if ($suffix instanceof AuraApplied) {
            $sourceGuid = $parsedEvent->getGenericData()->getSourceGuid();
            if ($sourceGuid instanceof Creature &&
                // Only actual creatures - not pets
                $sourceGuid->getUnitType() === Creature::CREATURE_UNIT_TYPE_CREATURE &&
                // Ignore summoned NPCs
                $this->summonedNpcs->search($sourceGuid->getId()) === false) {

                if (!$this->spellIdsForDungeon->has($currentDungeon->dungeon->id)) {
                    $this->spellIdsForDungeon->put($currentDungeon->dungeon->id, collect());
                }

                /** @var Collection $spellIdsForDungeon */
                $spellIdsForDungeon = $this->spellIdsForDungeon->get($currentDungeon->dungeon->id);

                // Assign spell IDs to dungeons
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

                            $this->log->extractDataAssignedDungeonToSpell($prefix->getSpellId(), $currentDungeon->dungeon->id);
                        }
                    }
                }

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

                        $this->log->extractDataAssignedSpellToNpc($npc->id, $prefix->getSpellId());
                    }
                }
            }
        }
    }

    public function afterExtract(ExtractedDataResult $result): void
    {
        // Now that we've extract spell data from the combat log, either update the data that's there in the database,
        // or fetch some additional info from Wowhead

        foreach ($this->spellIdsForDungeon as $dungeonId => $spellsByDungeon) {
            $this->log->afterExtractDungeonStart(__(Dungeon::findOrFail($dungeonId)->name, [], 'en_US'));

            foreach ($spellsByDungeon as $spellId => $combatLogEvent) {
                /**
                 * @var int            $spellId
                 * @var CombatLogEvent $combatLogEvent
                 */
                if ($this->allSpells->has($spellId)) {
                    // Update the spell based on a found combat log event
                    $this->updateSpell($result, $this->allSpells->get($spellId), $combatLogEvent);
                    continue;
                }

                // Create a new spell and fetch the info for it
                $createdSpell = $this->createSpellAndFetchInfo($result, $this->wowheadService, $spellId, $combatLogEvent);
                if ($createdSpell instanceof SpellModel) {
                    // Add to master list so that it doesn't get inserted twice
                    $this->allSpells->put($spellId, $createdSpell);

                    $this->log->afterExtractCreatedSpell($createdSpell->name, $spellId);
                }
            }
            $this->log->afterExtractDungeonEnd();
        }

        // Reset
        $this->spellIdsForDungeon = collect();
        $this->summonedNpcs       = collect();
    }

    private function updateSpell(ExtractedDataResult $result, SpellModel $spell, CombatLogEvent $combatLogEvent): bool
    {
        $spell->aura   = $combatLogEvent->getGenericData()->getDestGuid() instanceof Creature &&
            $combatLogEvent->getGenericData()->getDestGuid()->getUnitType() === Creature::CREATURE_UNIT_TYPE_CREATURE;
        $spell->debuff = $combatLogEvent->getGenericData()->getDestGuid() instanceof Player;

        if ($spell->isDirty() && $spell->save()) {
            $result->updatedSpell();

            return true;
        }

        return false;
        // Update aura state
//        return $spell->update([
//            'aura'   => $combatLogEvent->getGenericData()->getDestGuid() instanceof Creature &&
//                $combatLogEvent->getGenericData()->getDestGuid()->getUnitType() === Creature::CREATURE_UNIT_TYPE_CREATURE,
//            'debuff' => $combatLogEvent->getGenericData()->getDestGuid() instanceof Player,
//        ]);
    }

    private function createSpellAndFetchInfo(
        ExtractedDataResult     $result,
        WowheadServiceInterface $wowheadService,
        int                     $spellId,
        CombatLogEvent          $combatLogEvent
    ): ?SpellModel {
        $spellDataResult = $wowheadService->getSpellData($spellId);

        $destGuid = $combatLogEvent->getGenericData()->getDestGuid();

        $result->createdSpell();

        return SpellModel::create([
            'id'           => $spellId,
            'icon_name'    => $spellDataResult->getIconName(),
            'name'         => $spellDataResult->getName(),
            'dispel_type'  => $spellDataResult->getDispelType(),
            'schools_mask' => $spellDataResult->getSchoolsMask(),
            // Only when the target is a creature
            'aura'         => $destGuid instanceof Creature && $destGuid->getUnitType() === Creature::CREATURE_UNIT_TYPE_CREATURE,
        ]);
    }
}
