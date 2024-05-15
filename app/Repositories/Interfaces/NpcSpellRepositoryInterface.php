<?php

namespace App\Repositories\Interfaces;

use App\Models\NpcSpell;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method NpcSpell create(array $attributes)
 * @method NpcSpell find(int $id, array $columns = [])
 * @method NpcSpell findOrFail(int $id, array $columns = [])
 * @method NpcSpell findOrNew(int $id, array $columns = [])
 * @method bool save(NpcSpell $model)
 * @method bool update(NpcSpell $model, array $attributes = [], array $options = [])
 * @method bool delete(NpcSpell $model)
 * @method Collection<NpcSpell> all()
 */
interface NpcSpellRepositoryInterface extends BaseRepositoryInterface
{

}
