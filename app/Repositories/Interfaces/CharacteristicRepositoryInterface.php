<?php

namespace App\Repositories\Interfaces;

use App\Models\Characteristic;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method Characteristic             create(array $attributes)
 * @method Characteristic|null        find(int $id, array|string $columns = ['*'])
 * @method Characteristic             findOrFail(int $id, array|string $columns = ['*'])
 * @method Characteristic             findOrNew(int $id, array|string $columns = ['*'])
 * @method bool                       save(Characteristic $model)
 * @method bool                       update(Characteristic $model, array $attributes = [], array $options = [])
 * @method bool                       delete(Characteristic $model)
 * @method Collection<Characteristic> all()
 * @method bool                       exists(array $columns)
 */
interface CharacteristicRepositoryInterface extends BaseRepositoryInterface
{
}
