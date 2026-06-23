<?php

namespace App\Repositories\Interfaces\Npc;

use App\Models\Npc\NpcHealth;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method NpcHealth                  create(array<string, mixed> $attributes)
 * @method NpcHealth|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method NpcHealth                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method NpcHealth                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                       save(NpcHealth $model)
 * @method bool                       update(NpcHealth $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                       delete(NpcHealth $model)
 * @method Collection<int, NpcHealth> all()
 * @method bool                       exists(array<int, string> $columns)
 */
interface NpcHealthRepositoryInterface extends BaseRepositoryInterface
{
}
