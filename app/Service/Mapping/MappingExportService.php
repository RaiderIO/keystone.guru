<?php

namespace App\Service\Mapping;

use App\Models\Npc\Npc;
use App\Models\Spell\Spell;
use App\Models\Spell\SpellTuningChange;

class MappingExportService implements MappingExportServiceInterface
{
    /**
     * {@inheritDoc}
     */
    public function serializeSpells(): array
    {
        // The effects are eager loaded (rather than lazily accessed) so they are serialized into the
        // spells.json output - they are inferred from the game client's data, so every environment has
        // to be told about them rather than deriving them itself.
        $spells = Spell::query()->with(['spellEffects'])->get();
        foreach ($spells as $spell) {
            $spell->spellEffects->makeHidden(['id', 'spell_id', 'spell']);

            // icon_url, wowhead_url and wowhead_tooltip_data are computed accessors in Spell::$appends,
            // not columns - every entry in that list must be hidden here or it lands in the seeder file.
            // The combat-log-derived columns must not round-trip through the git seeders either; they are
            // re-applied per environment from the combatlog pipeline, and SpellRelationMapping preserves
            // them across a re-seed from the same constant.
            $spell->makeHidden([
                'icon_url',
                'wowhead_url',
                'wowhead_tooltip_data',
                'tooltip_data',
                ...Spell::COMBAT_LOG_DERIVED_COLUMNS,
            ]);
        }

        return $spells->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function serializeNpcs(): array
    {
        // Save all NPCs which aren't directly tied to a dungeon. The relations below are eager loaded
        // (rather than lazily accessed) so they are serialized into the npcs.json output.
        // Note: the order of these relations determines the key order in npcs.json - keep it stable.
        $npcs = Npc::query()->with([
            'npcbolsteringwhitelists',
            'npcHealths',
            'npcEnemyForces',
            'npcDungeons',
        ])
            ->get()
            ->values();

        foreach ($npcs as $npc) {
            // wowhead_url is a computed accessor in Npc::$appends, not a column, and it now depends on
            // the mapping version being viewed - a value frozen into the seeder file would be a guess
            $npc->makeHidden([
                'enemy_portrait_url',
                'wowhead_url',
            ]);
            $npc->npcbolsteringwhitelists->makeHidden(['whitelistnpc']);
            foreach ($npc->npcDungeons as $npcDungeon) {
                $npcDungeon->makeHidden(['dungeon']);
            }
        }

        return $npcs->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function serializeSpellTuningChanges(): array
    {
        // The id is assigned on insert by whichever environment loads the file; leaving it out keeps the
        // file from churning on every re-run, and the explicit order keeps it deterministic.
        $changes = SpellTuningChange::query()
            ->orderBy('to_build_number')
            ->orderBy('spell_id')
            ->orderBy('value_index')
            ->orderBy('id')
            ->get()
            ->makeHidden(['id']);

        return $changes->toArray();
    }
}
