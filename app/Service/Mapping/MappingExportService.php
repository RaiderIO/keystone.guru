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
        // Save all NPCs which aren't directly tied to a dungeon. npcbolsteringwhitelists is eager loaded
        // here (rather than lazily accessed) so this also works under preventLazyLoading on the HTTP path.
        $npcs = Npc::without([
            'characteristics',
            'spells',
            'enemyForces',
        ])
            ->with([
                'npcEnemyForces',
                'npcDungeons',
                'npcbolsteringwhitelists',
            ])
            ->get()
            ->values();

        foreach ($npcs as $npc) {
            $npc->makeHidden([
                'type',
                'class',
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
