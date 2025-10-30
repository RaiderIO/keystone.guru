<?php

namespace App\Logic\MapContext;

use App\Models\Dungeon;
use App\Models\Npc\Npc;
use App\Models\Spell\Spell;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Cache\Traits\RemembersToFile;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Psr\SimpleCache\InvalidArgumentException;

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
     */
    public function toArray(): array
    {
        // Npc data (for localizations)
        $dungeonNpcDataKey = sprintf('dungeon_npcs_%d_%s', $this->dungeon->id, $this->locale);
        $dungeonNpcData    = $this->rememberLocal($dungeonNpcDataKey, 86400, function () use (
            $dungeonNpcDataKey,
        ) {
            return $this->cacheService->remember(
                $dungeonNpcDataKey,
                function () {
                    return $this->dungeon->npcs()
                            ->selectRaw('npcs.*, translations.translation as name')
                            ->leftJoin('translations', function (JoinClause $clause) {
                                $clause->on('translations.key', 'npcs.name')
                                    ->on('translations.locale', DB::raw(sprintf('"%s"', $this->locale)));
                            })
                            ->with([
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
                                'characteristics',
                            ])
                        ->values();
                },
                config('keystoneguru.cache.dungeonData.ttl'),
            );
        });

        // Unique, localized spells for the dungeon (referenced by ID from NPCs)
        $dungeonSpellsKey = sprintf('dungeon_spells_%d_%s', $this->dungeon->id, $this->locale);
        $dungeonSpells    = $this->rememberLocal($dungeonSpellsKey, 86400, function () use ($dungeonSpellsKey) {
            return $this->cacheService->remember(
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
                    return Spell::query()
                        ->selectRaw('spells.*, translations.translation as name')
                        ->leftJoin('translations', function (JoinClause $clause) {
                            $clause->on('translations.key', 'spells.name')
                                ->on('translations.locale', DB::raw(sprintf('"%s"', $this->locale)));
                        })
                        ->whereIn('spells.id', $spellIds)
                        ->get()
                        ->makeHidden([
                            'cooldown_group',
                            'dispel_type',
                            'mechanic',
                            'schools_mask',
                            'miss_types_mask',
                            'debuff',
                            'cast_time',
                            'duration',
                            'selectable',
                            'fetched_data_at',
                        ])
                        ->keyBy('id')
                        ->values();
                },
                config('keystoneguru.cache.dungeonData.ttl'),
            );
        });

        return [
            'dungeonNpcs'   => $dungeonNpcData,
            'dungeonSpells' => $dungeonSpells,
        ];
    }
}
