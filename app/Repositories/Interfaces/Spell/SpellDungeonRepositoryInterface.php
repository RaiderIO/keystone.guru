<?php

namespace App\Repositories\Interfaces\Spell;

use App\Models\Spell\SpellDungeon;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method SpellDungeon                  create(array<string, mixed> $attributes)
 * @method SpellDungeon|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method SpellDungeon                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method SpellDungeon                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                          save(SpellDungeon $model)
 * @method bool                          update(SpellDungeon $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                          delete(SpellDungeon $model)
 * @method Collection<int, SpellDungeon> all()
 * @method bool                          exists(array<int, string> $columns)
 */
interface SpellDungeonRepositoryInterface extends BaseRepositoryInterface
{
}
