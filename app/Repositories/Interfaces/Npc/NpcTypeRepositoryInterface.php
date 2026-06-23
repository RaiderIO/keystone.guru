<?php

namespace App\Repositories\Interfaces\Npc;

use App\Models\Npc\NpcType;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method NpcType                  create(array<string, mixed> $attributes)
 * @method NpcType|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method NpcType                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method NpcType                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                     save(NpcType $model)
 * @method bool                     update(NpcType $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                     delete(NpcType $model)
 * @method Collection<int, NpcType> all()
 * @method bool                     exists(array<int, string> $columns)
 */
interface NpcTypeRepositoryInterface extends BaseRepositoryInterface
{
}
