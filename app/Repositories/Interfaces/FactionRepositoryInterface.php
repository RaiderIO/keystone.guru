<?php

namespace App\Repositories\Interfaces;

use App\Models\Faction;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method Faction             create(array $attributes)
 * @method Faction|null        find(int $id, array|string $columns = ['*'])
 * @method Faction             findOrFail(int $id, array|string $columns = ['*'])
 * @method Faction             findOrNew(int $id, array|string $columns = ['*'])
 * @method bool                save(Faction $model)
 * @method bool                update(Faction $model, array $attributes = [], array $options = [])
 * @method bool                delete(Faction $model)
 * @method Collection<Faction> all()
 */
interface FactionRepositoryInterface extends BaseRepositoryInterface
{
}
