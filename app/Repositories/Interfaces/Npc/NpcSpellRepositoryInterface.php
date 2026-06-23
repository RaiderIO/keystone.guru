<?php

namespace App\Repositories\Interfaces\Npc;

use App\Models\Npc\NpcSpell;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method NpcSpell                  create(array<string, mixed> $attributes)
 * @method NpcSpell|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method NpcSpell                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method NpcSpell                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                      save(NpcSpell $model)
 * @method bool                      update(NpcSpell $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                      delete(NpcSpell $model)
 * @method Collection<int, NpcSpell> all()
 * @method bool                      exists(array<int, string> $columns)
 */
interface NpcSpellRepositoryInterface extends BaseRepositoryInterface
{
}
