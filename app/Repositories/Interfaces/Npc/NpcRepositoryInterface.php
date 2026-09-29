<?php

namespace App\Repositories\Interfaces\Npc;

use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * @method Npc                  create(array<string, mixed> $attributes)
 * @method Npc|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method Npc                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method Npc                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                 save(Npc $model)
 * @method bool                 update(Npc $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                 delete(Npc $model)
 * @method Collection<int, Npc> all()
 * @method bool                 exists(array<string, mixed> $columns)
 */
interface NpcRepositoryInterface extends BaseRepositoryInterface
{
    /** The SQL expression for a dungeon's name in {@see self::getAdminListBuilder()}, falling back to English. */
    public const string ADMIN_LIST_DUNGEON_NAME_EXPRESSION = "COALESCE(NULLIF(dungeon_translations.translation, ''), dungeon_fallback_translations.translation)";

    /**
     * @return Collection<int, Npc>
     */
    public function getInUseNpcs(MappingVersion $mappingVersion): Collection;

    /**
     * @param  Collection<int, Npc>|null $inUseNpcs
     * @return Collection<int, int>
     */
    public function getInUseNpcIds(MappingVersion $mappingVersion, ?Collection $inUseNpcs = null): Collection;

    /**
     * NPCs keyed by id, with the relations the compendium's NPC link hover tooltips read (#4096) eager-loaded.
     *
     * @param  Collection<int, int> $npcIds
     * @return Collection<int, Npc>
     */
    public function findAllByIdWithTooltipRelations(Collection $npcIds): Collection;

    /**
     * One row per NPC for the admin NPC list, with its name and dungeon names in the given locale and its enemy
     * count on each dungeon's latest mapping version.
     *
     * @return Builder<Npc>
     */
    public function getAdminListBuilder(string $locale): Builder;
}
