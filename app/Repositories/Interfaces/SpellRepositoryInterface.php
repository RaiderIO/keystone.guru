<?php

namespace App\Repositories\Interfaces;

use App\Models\Spell\Spell;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method Spell             create(array $attributes)
 * @method Spell|null        find(int $id, array|string $columns = ['*'])
 * @method Spell             findOrFail(int $id, array|string $columns = ['*'])
 * @method Spell             findOrNew(int $id, array|string $columns = ['*'])
 * @method bool              save(Spell $model)
 * @method bool              update(Spell $model, array $attributes = [], array $options = [])
 * @method bool              delete(Spell $model)
 * @method Collection<Spell> all()
 * @method bool              exists(array $columns)
 */
interface SpellRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return int[]
     */
    public function getMissingSpellIds(): array;

    /**
     * @param  Collection<int>   $spellIds
     * @return Collection<Spell>
     */
    public function findAllById(Collection $spellIds): Collection;

    /**
     * @return Collection<int, Spell> keyed by spell ID
     */
    public function getAllWithCharacteristic(): Collection;
}
