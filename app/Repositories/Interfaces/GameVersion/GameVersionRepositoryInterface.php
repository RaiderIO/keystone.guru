<?php

namespace App\Repositories\Interfaces\GameVersion;

use App\Models\GameVersion\GameVersion;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method GameVersion create(array $attributes)
 * @method GameVersion|null find(int $id, array|string $columns = ['*'])
 * @method GameVersion findOrFail(int $id, array|string $columns = ['*'])
 * @method GameVersion findOrNew(int $id, array|string $columns = ['*'])
 * @method bool save(GameVersion $model)
 * @method bool update(GameVersion $model, array $attributes = [], array $options = [])
 * @method bool delete(GameVersion $model)
 * @method Collection<GameVersion> all()
 */
interface GameVersionRepositoryInterface extends BaseRepositoryInterface
{

}
