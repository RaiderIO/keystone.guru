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
        // Model caching is disabled for this query: it's a one-off admin export of the entire spell
        // table, and caching the whole result set as a single serialized cache entry made retrieval
        // (unserialize()) exhaust the 512MB memory limit in production (Sentry PHP-LARAVEL-V0).
        // `disableModelCaching()` only exists on genealabs/laravel-model-caching's CachedBuilder,
        // which `query()` only returns when model caching is enabled - in environments where it's
        // off (tests, local dev) `query()` returns a plain Eloquent Builder without that method.
        $spellsQuery = Spell::query();

        if (method_exists($spellsQuery, 'disableModelCaching')) {
            $spellsQuery->disableModelCaching();
        }

        $spells = $spellsQuery->with(['spellEffects'])->get();
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
        // Model caching is disabled for the same reason as serializeSpells() above.
        $npcsQuery = Npc::query();

        if (method_exists($npcsQuery, 'disableModelCaching')) {
            $npcsQuery->disableModelCaching();
        }

        $npcs = $npcsQuery->with([
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

    /**
     * {@inheritDoc}
     */
    public function serializeSpellTuningChanges(): array
    {
        // Model caching is disabled for the same reason as serializeSpells() above.
        $changesQuery = SpellTuningChange::query();

        if (method_exists($changesQuery, 'disableModelCaching')) {
            $changesQuery->disableModelCaching();
        }

        // The id is assigned on insert by whichever environment loads the file; leaving it out keeps the
        // file from churning on every re-run, and the explicit order keeps it deterministic.
        $changes = $changesQuery
            ->orderBy('to_build_number')
            ->orderBy('spell_id')
            ->orderBy('value_index')
            ->orderBy('id')
            ->get()
            ->makeHidden(['id']);

        return $changes->toArray();
    }
}
