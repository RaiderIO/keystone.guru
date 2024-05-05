<?php

namespace App\Repositories\Interfaces;

use App\Models\GameIcon;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method GameIcon create(array $attributes)
 * @method GameIcon find(int $id, array $columns = [])
 * @method GameIcon findOrFail(int $id, array $columns = [])
 * @method GameIcon findOrNew(int $id, array $columns = [])
 * @method bool save(GameIcon $model)
 * @method bool update(GameIcon $model, array $attributes = [], array $options = [])
 * @method bool delete(GameIcon $model)
 * @method Collection<GameIcon> all()
 */
interface GameIconRepositoryInterface extends BaseRepositoryInterface
{

}
