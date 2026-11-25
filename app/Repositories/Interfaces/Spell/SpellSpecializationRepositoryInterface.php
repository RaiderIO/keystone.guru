<?php

namespace App\Repositories\Interfaces\Spell;

use App\Models\Spell\SpellSpecialization;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method SpellSpecialization                  create(array<string, mixed> $attributes)
 * @method SpellSpecialization|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method SpellSpecialization                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method SpellSpecialization                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                 save(SpellSpecialization $model)
 * @method bool                                 update(SpellSpecialization $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                 delete(SpellSpecialization $model)
 * @method Collection<int, SpellSpecialization> all()
 * @method bool                                 exists(array<int, string> $columns)
 */
interface SpellSpecializationRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Make each given spell's specializations exactly the ones listed for it. Spells left out of the list keep
     * whatever they had.
     *
     * @param  array<int, list<int>> $specializationIdsBySpellId spell id => character_class_specializations.id
     * @return int                   how many spells' specializations changed
     */
    public function replaceForSpells(array $specializationIdsBySpellId): int;
}
