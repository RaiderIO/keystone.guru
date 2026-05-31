<?php

namespace App\Service\CombatLog\DataExtractors\SpellDataCollectors;

use App\Logic\CombatLog\CombatEvents\CombatLogEvent;
use App\Logic\CombatLog\CombatEvents\Prefixes\Spell;
use App\Logic\CombatLog\Guid\Creature;
use App\Models\CombatLog\CombatLogNpcEvent;
use App\Models\CombatLog\CombatLogNpcEventType;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcSpell;
use App\Models\Spell\Spell as SpellModel;
use App\Models\Spell\SpellDungeon;
use App\Service\CombatLog\DataExtractors\Logging\SpellDataExtractorLoggingInterface;
use App\Service\CombatLog\Dtos\DataExtraction\DataExtractionCurrentDungeon;
use App\Service\CombatLog\Dtos\DataExtraction\ExtractedDataResult;
use Illuminate\Support\Collection;

class NpcSpellAssignmentCollector implements SpellDataCollectorInterface
{
    /** @var Collection<int, Npc|false> */
    private Collection $npcCache;

    /**
     * New (npc_id, spell_id, dungeon_id) triples discovered this session — written in afterCollect.
     *
     * @var Collection<string, array{npc_id: int, spell_id: int, dungeon_id: int}>
     */
    private Collection $pendingNpcSpellAssignments;

    private ?string $currentCombatLogFilePath = null;

    /**
     * @param Collection<int, SpellModel> $allSpells
     */
    public function __construct(
        private readonly Collection                         $allSpells,
        private readonly SpellDataExtractorLoggingInterface $log,
    ) {
        $this->npcCache                   = collect();
        $this->pendingNpcSpellAssignments = collect();
    }

    public function beforeCollect(string $combatLogFilePath): void
    {
        $this->currentCombatLogFilePath = $combatLogFilePath;
    }

    public function collect(
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

    public function afterCollect(ExtractedDataResult $result, string $combatLogFilePath): void
    {
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

        $this->npcCache                   = collect();
        $this->pendingNpcSpellAssignments = collect();
        $this->currentCombatLogFilePath   = null;
    }
}
