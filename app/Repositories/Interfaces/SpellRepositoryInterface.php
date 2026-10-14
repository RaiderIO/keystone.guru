<?php

namespace App\Repositories\Interfaces;

use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;
use App\Models\Spell\Spell;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method Spell                  create(array<string, mixed> $attributes)
 * @method Spell|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method Spell                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method Spell                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                   save(Spell $model)
 * @method bool                   update(Spell $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                   delete(Spell $model)
 * @method Collection<int, Spell> all()
 * @method bool                   exists(array<string, mixed> $columns)
 */
interface SpellRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return int[]
     */
    public function getMissingSpellIds(): array;

    /**
     * @param  Collection<int, int>   $spellIds
     * @return Collection<int, Spell>
     */
    public function findAllById(Collection $spellIds): Collection;

    /**
     * The same spells, with the relations their hover tooltip reads - the counterpart of
     * NpcRepositoryInterface::findAllByIdWithTooltipRelations(). Kept apart from findAllById() because
     * its other callers render no tooltip and would pay for the relation for nothing.
     *
     * @param  Collection<int, int>|Collection<int, Spell> $spellIds
     * @return Collection<int, Spell>
     */
    public function findAllByIdWithTooltipRelations(Collection $spellIds): Collection;

    /**
     * @return Collection<int, Spell> keyed by spell ID
     */
    public function getAllWithCharacteristic(): Collection;

    /**
     * @return Collection<int, Spell> all spells with their spellDungeons relation loaded, keyed by spell ID
     */
    public function getAllKeyedWithSpellDungeons(): Collection;

    /**
     * The ids of every spell flagged `hidden_on_map`.
     *
     * @return Collection<int, int>
     */
    public function getHiddenOnMapSpellIds(): Collection;

    /**
     * The non-PVP spells of the given category that carry a characteristic, with the relations the class
     * compendium renders. Restricted to the mapping version's game version when one is given.
     *
     * @return Collection<int, Spell>
     */
    public function getCharacteristicSpellsForCategory(string $category, ?MappingVersion $mappingVersion): Collection;

    /**
     * The visible spells whose counters mask contains the given bit and that were observed in the dungeon.
     * Restricted to the mapping version's game version when one is given.
     *
     * @return Collection<int, Spell>
     */
    public function getVisibleSpellsByCounterBitForDungeon(int $counterBit, Dungeon $dungeon, ?MappingVersion $mappingVersion): Collection;

    /**
     * The visible spells that were observed being reflected in the dungeon. Restricted to the mapping
     * version's game version when one is given.
     *
     * @return Collection<int, Spell>
     */
    public function getVisibleReflectedSpellsForDungeon(Dungeon $dungeon, ?MappingVersion $mappingVersion): Collection;

    /**
     * Every spell with its game version, keyed by spell ID, optionally limited to the spells seen in a dungeon.
     *
     * @return Collection<int, Spell>
     */
    public function getAllWithGameVersionKeyedById(?Dungeon $dungeon = null): Collection;
}
