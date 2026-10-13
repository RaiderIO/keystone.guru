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
use App\Models\Spell\SpellCategory;
use App\Models\Spell\SpellDungeon;
use App\Service\CombatLog\DataExtractors\Logging\SpellDataExtractorLoggingInterface;
use App\Service\CombatLog\Dtos\DataExtraction\DataExtractionCurrentDungeon;
use App\Service\CombatLog\Dtos\DataExtraction\ExtractedDataResult;
use Illuminate\Support\Collection;

class NpcSpellAssignmentCollector implements SpellDataCollectorInterface
{
    /**
     * Every (npc_id, spell_id) pair cast this session with the dungeon and raw event of its first cast - resolved
     * against the NPCs' known spells and written in afterCollect.
     *
     * @var array<string, array{npc_id: int, spell_id: int, dungeon_id: int, raw_event: string}>
     */
    private array $castNpcSpells = [];

    private ?string $currentCombatLogFilePath = null;

    /**
     * @param Collection<int, SpellModel> $allSpells
     */
    public function __construct(
        private readonly Collection                         $allSpells,
        private readonly SpellDataExtractorLoggingInterface $log,
    ) {
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
        if ($spell === null || $spell->category !== SpellCategory::Unknown->translationKey()) {
            return;
        }

        $npcId    = $sourceGuid->getId();
        $spellId  = $prefix->getSpellId();
        $dedupKey = sprintf('%d-%d', $npcId, $spellId);

        $this->castNpcSpells[$dedupKey] ??= [
            'npc_id'     => $npcId,
            'spell_id'   => $spellId,
            'dungeon_id' => $currentDungeon->dungeon->id,
            'raw_event'  => $parsedEvent->getRawEvent(),
        ];
    }

    public function afterCollect(ExtractedDataResult $result, string $combatLogFilePath): void
    {
        $npcs = $this->loadNpcsWithSpells();

        foreach ($this->castNpcSpells as $castNpcSpell) {
            /** @var Npc|null $npc */
            $npc = $npcs->get($castNpcSpell['npc_id']);
            if ($npc === null) {
                $this->log->extractDataSpellNpcNull($castNpcSpell['npc_id']);

                continue;
            }

            if ($npc->npcSpells->contains('spell_id', $castNpcSpell['spell_id'])) {
                continue;
            }

            // This NPC now casts this spell - we have proof
            $this->log->extractDataAssignedSpellToNpc($npc->id, $castNpcSpell['spell_id'], $castNpcSpell['raw_event']);

            NpcSpell::create([
                'npc_id'   => $castNpcSpell['npc_id'],
                'spell_id' => $castNpcSpell['spell_id'],
            ]);

            // insertOrIgnore (not exists()+create()) so a concurrent extraction job racing this
            // same pair cannot create a duplicate row - the unique index makes the second insert
            // a no-op instead of a duplicate row, and it's one query instead of two either way
            SpellDungeon::query()->insertOrIgnore([
                'spell_id'   => $castNpcSpell['spell_id'],
                'dungeon_id' => $castNpcSpell['dungeon_id'],
            ]);

            CombatLogNpcEvent::create([
                'npc_id'          => $castNpcSpell['npc_id'],
                'event_type'      => CombatLogNpcEventType::SpellAssigned,
                'model_class'     => SpellModel::class,
                'model_id'        => $castNpcSpell['spell_id'],
                'combat_log_path' => $this->currentCombatLogFilePath,
            ]);

            $result->createdNpcSpell();
        }

        $this->castNpcSpells            = [];
        $this->currentCombatLogFilePath = null;
    }

    /**
     * Every NPC that cast a candidate spell this session, loaded in one query.
     *
     * @return Collection<int, Npc>
     */
    private function loadNpcsWithSpells(): Collection
    {
        $npcIds = array_values(array_unique(array_column($this->castNpcSpells, 'npc_id')));
        if (empty($npcIds)) {
            return collect();
        }

        return Npc::with('npcSpells')
            ->whereIn('id', $npcIds)
            ->get()
            ->keyBy('id');
    }
}
