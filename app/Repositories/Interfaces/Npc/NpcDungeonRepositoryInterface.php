<?php

namespace App\Repositories\Interfaces\Npc;

use App\Models\Npc\NpcDungeon;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method NpcDungeon                  create(array<string, mixed> $attributes)
 * @method NpcDungeon|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method NpcDungeon                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method NpcDungeon                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                        save(NpcDungeon $model)
 * @method bool                        update(NpcDungeon $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                        delete(NpcDungeon $model)
 * @method Collection<int, NpcDungeon> all()
 * @method bool                        exists(array<int, string> $columns)
 */
interface NpcDungeonRepositoryInterface extends BaseRepositoryInterface
{
}
