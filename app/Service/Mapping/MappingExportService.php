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
        $spells = Spell::all();
        foreach ($spells as $spell) {
            // aura, debuff and miss_types_mask are combat-log-derived behavior - they must not round-trip
            // through the git seeders; they are re-applied per environment from the combatlog pipeline.
            $spell->makeHidden([
                'icon_url',
                'wowhead_url',
                'aura',
                'debuff',
                'miss_types_mask',
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
