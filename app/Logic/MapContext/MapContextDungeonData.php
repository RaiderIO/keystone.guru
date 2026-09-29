<?php

namespace App\Logic\MapContext;

use App\Logic\Datatables\ColumnHandler\Npc\NameColumnHandler as NpcNameColumnHandler;
use App\Logic\Datatables\ColumnHandler\Spell\NameColumnHandler as SpellNameColumnHandler;
use App\Models\Dungeon;
use App\Models\Npc\Npc;
use App\Models\Spell\Spell;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Cache\Traits\RemembersToFile;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Contracts\Support\Arrayable;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * @implements Arrayable<string, mixed>
 */
class MapContextDungeonData implements Arrayable
{
    use RemembersToFile;

    public function __construct(
        protected CacheServiceInterface       $cacheService,
        protected CoordinatesServiceInterface $coordinatesService,
        protected Dungeon                     $dungeon,
        protected string                      $locale,
    ) {
    }

    /**
     * @throws InvalidArgumentException
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        // Npc data (for localizations)
        $dungeonNpcDataKey = sprintf('dungeon_npcs_%d_%s', $this->dungeon->id, $this->locale);
        $dungeonNpcData    = $this->rememberLocal($dungeonNpcDataKey, 86400, fn() => $this->cacheService->remember(
            $dungeonNpcDataKey,
            fn() => NpcNameColumnHandler::joinNameTranslations(
                $this->dungeon->npcs()->getQuery(),
                $this->locale,
                config('app.fallback_locale'),
            )
                ->selectRaw(sprintf('npcs.*, %s as name', NpcNameColumnHandler::NAME_EXPRESSION))
                ->with([
                    // The front-end reads these relations off the npc objects in this payload (enemy visuals + tooltips)
                    'type',
                    'class',
                    'npcbolsteringwhitelists',
                    'npcHealths',
                    // Return only spell IDs for each NPC
                    'spells:id',
                ])
                ->get()
                    // Map the spells relation to an array of IDs to avoid serializing full models
                ->map(function (Npc $npc) {
                    $npc->setAttribute('spell_ids', $npc->spells->pluck('id')->values());
                    // Remove the full spells relation from output
                    $npc->unsetRelation('spells');

                    return $npc;
                })
                ->makeHidden([
                    'display_id',
                    'encounter_id',
                    'level',
                    'mdt_scale',
                    'pivot',
                    // This payload is cached per dungeon, not per mapping version, so it cannot carry a
                    // correct wowhead_url - nothing in the front-end reads it
                    'wowhead_url',
                ])
                ->values(),
            config('keystoneguru.cache.dungeonData.ttl'),
        ));

        // Unique, localized spells for the dungeon (referenced by ID from NPCs)
        $dungeonSpellsKey = sprintf('dungeon_spells_%d_%s', $this->dungeon->id, $this->locale);
        $dungeonSpells    = $this->rememberLocal($dungeonSpellsKey, 86400, fn() => $this->cacheService->remember(
            $dungeonSpellsKey,
            function () {
                // Gather unique spell IDs from all NPCs in the dungeon
                $spellIds = $this->dungeon->npcs()
                    ->with(['spells:id'])
                    ->get()
                    ->flatMap(fn(Npc $npc) => $npc->spells->pluck('id'))
                    ->unique()
                    ->values();

                if ($spellIds->isEmpty()) {
                    return collect();
                }

                // Load full spell data once, with localization
                return SpellNameColumnHandler::joinNameTranslations(Spell::query(), $this->locale, config('app.fallback_locale'))
                    ->selectRaw(sprintf('spells.*, %s as name', SpellNameColumnHandler::NAME_EXPRESSION))
                    ->whereIn('spells.id', $spellIds)
                    ->get()
                    ->makeHidden([
                        'debuff',
                        'selectable',
                        'fetched_data_at',
                        // Nothing on the map renders a spell description, and one per spell of the dungeon
                        // would grow the map context by a fair amount of text. The effects a description
                        // is rendered from are not listed because this query does not load that relation -
                        // add them here the day it does, or the coefficients ship with every map (#3972 review)
                        'description_template',
                        'description_format',
                        'description_values',
                        'tooltip_data',
                    ])
                    ->keyBy('id')
                    ->values();
            },
            config('keystoneguru.cache.dungeonData.ttl'),
        ));

        return [
            'dungeonNpcs'   => $dungeonNpcData,
            'dungeonSpells' => $dungeonSpells,
        ];
    }
}
