<?php

namespace App\Repositories\Interfaces;

use App\Models\Spell;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method Spell create(array $attributes)
 * @method Spell find(int $id, array $columns = [])
 * @method Spell findOrFail(int $id, array $columns = [])
 * @method Spell findOrNew(int $id, array $columns = [])
 * @method bool save(Spell $model)
 * @method bool update(Spell $model, array $attributes = [], array $options = [])
 * @method bool delete(Spell $model)
 * @method Collection<Spell> all()
 */
interface SpellRepositoryInterface extends BaseRepositoryInterface
{

}
