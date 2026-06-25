<?php

namespace App\Repositories\Interfaces;

use App\Models\GameServerRegion;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method GameServerRegion                  create(array<string, mixed> $attributes)
 * @method GameServerRegion|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method GameServerRegion                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method GameServerRegion                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                              save(GameServerRegion $model)
 * @method bool                              update(GameServerRegion $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                              delete(GameServerRegion $model)
 * @method Collection<int, GameServerRegion> all()
 * @method bool                              exists(array<int, string> $columns)
 */
interface GameServerRegionRepositoryInterface extends BaseRepositoryInterface
{
}
