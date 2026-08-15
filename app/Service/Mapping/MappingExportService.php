<?php

namespace App\Service\Mapping;

use App\Models\Npc\Npc;
use App\Models\Spell\Spell;

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
        $spells = Spell::with(['spellEffects'])->get();
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
        $npcs = Npc::with([
            'npcbolsteringwhitelists',
            'npcHealths',
            'npcEnemyForces',
            'npcDungeons',
        ])
            ->get()
            ->values();

        foreach ($npcs as $npc) {
            $npc->makeHidden([
                'enemy_portrait_url',
            ]);
            $npc->npcbolsteringwhitelists->makeHidden(['whitelistnpc']);
            foreach ($npc->npcDungeons as $npcDungeon) {
                $npcDungeon->makeHidden(['dungeon']);
            }
        }

        return $npcs->toArray();
    }
}
