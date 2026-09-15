<?php

namespace App\Repositories\Interfaces\Spell;

use App\Models\Spell\SpellEffect;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method SpellEffect                  create(array<string, mixed> $attributes)
 * @method SpellEffect|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method SpellEffect                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method SpellEffect                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                         save(SpellEffect $model)
 * @method bool                         update(SpellEffect $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                         delete(SpellEffect $model)
 * @method Collection<int, SpellEffect> all()
 * @method bool                         exists(array<int, string> $columns)
 */
interface SpellEffectRepositoryInterface extends BaseRepositoryInterface
{
}
