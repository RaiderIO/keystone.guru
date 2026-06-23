<?php

namespace App\Repositories\Interfaces\Npc;

use App\Models\Npc\NpcClass;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method NpcClass                  create(array<string, mixed> $attributes)
 * @method NpcClass|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method NpcClass                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method NpcClass                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                      save(NpcClass $model)
 * @method bool                      update(NpcClass $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                      delete(NpcClass $model)
 * @method Collection<int, NpcClass> all()
 * @method bool                      exists(array<int, string> $columns)
 */
interface NpcClassRepositoryInterface extends BaseRepositoryInterface
{
}
