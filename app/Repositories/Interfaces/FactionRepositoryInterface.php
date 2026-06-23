<?php

namespace App\Repositories\Interfaces;

use App\Models\Faction;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method Faction                  create(array<string, mixed> $attributes)
 * @method Faction|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method Faction                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method Faction                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                     save(Faction $model)
 * @method bool                     update(Faction $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                     delete(Faction $model)
 * @method Collection<int, Faction> all()
 * @method bool                     exists(array<int, string> $columns)
 */
interface FactionRepositoryInterface extends BaseRepositoryInterface
{
}
