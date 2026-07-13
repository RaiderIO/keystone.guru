<?php

namespace App\Repositories\Interfaces;

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
     * @return Collection<int, Spell> keyed by spell ID
     */
    public function getAllWithCharacteristic(): Collection;
}
