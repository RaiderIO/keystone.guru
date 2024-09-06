<?php

namespace App\Repositories\Interfaces;

use App\Models\GameServerRegion;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method GameServerRegion create(array $attributes)
 * @method GameServerRegion|null find(int $id, array|string $columns = ['*'])
 * @method GameServerRegion findOrFail(int $id, array|string $columns = ['*'])
 * @method GameServerRegion findOrNew(int $id, array|string $columns = ['*'])
 * @method bool save(GameServerRegion $model)
 * @method bool update(GameServerRegion $model, array $attributes = [], array $options = [])
 * @method bool delete(GameServerRegion $model)
 * @method Collection<GameServerRegion> all()
 */
interface GameServerRegionRepositoryInterface extends BaseRepositoryInterface
{

}
