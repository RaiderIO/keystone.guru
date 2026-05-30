<?php

namespace App\Repositories\Interfaces\Npc;

use App\Models\Npc\NpcCharacteristic;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method NpcCharacteristic             create(array $attributes)
 * @method NpcCharacteristic|null        find(int $id, array|string $columns = ['*'])
 * @method NpcCharacteristic             findOrFail(int $id, array|string $columns = ['*'])
 * @method NpcCharacteristic             findOrNew(int $id, array|string $columns = ['*'])
 * @method bool                          save(NpcCharacteristic $model)
 * @method bool                          update(NpcCharacteristic $model, array $attributes = [], array $options = [])
 * @method bool                          delete(NpcCharacteristic $model)
 * @method Collection<NpcCharacteristic> all()
 * @method bool                          exists(array $columns)
 */
interface NpcCharacteristicRepositoryInterface extends BaseRepositoryInterface
{
}
