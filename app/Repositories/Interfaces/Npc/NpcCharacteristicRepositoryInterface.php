<?php

namespace App\Repositories\Interfaces\Npc;

use App\Models\Npc\NpcCharacteristic;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method NpcCharacteristic                  create(array<string, mixed> $attributes)
 * @method NpcCharacteristic|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method NpcCharacteristic                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method NpcCharacteristic                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                               save(NpcCharacteristic $model)
 * @method bool                               update(NpcCharacteristic $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                               delete(NpcCharacteristic $model)
 * @method Collection<int, NpcCharacteristic> all()
 * @method bool                               exists(array<int, string> $columns)
 */
interface NpcCharacteristicRepositoryInterface extends BaseRepositoryInterface
{
}
