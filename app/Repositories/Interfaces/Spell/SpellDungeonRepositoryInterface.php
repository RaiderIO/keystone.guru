<?php

namespace App\Repositories\Interfaces\Spell;

use App\Models\Spell\SpellDungeon;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method SpellDungeon             create(array $attributes)
 * @method SpellDungeon|null        find(int $id, array|string $columns = ['*'])
 * @method SpellDungeon             findOrFail(int $id, array|string $columns = ['*'])
 * @method SpellDungeon             findOrNew(int $id, array|string $columns = ['*'])
 * @method bool                     save(SpellDungeon $model)
 * @method bool                     update(SpellDungeon $model, array $attributes = [], array $options = [])
 * @method bool                     delete(SpellDungeon $model)
 * @method Collection<SpellDungeon> all()
 * @method bool                     exists(array $columns)
 */
interface SpellDungeonRepositoryInterface extends BaseRepositoryInterface
{
}
