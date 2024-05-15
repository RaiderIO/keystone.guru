<?php

namespace App\Repositories\Interfaces;

use App\Models\Season;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method Season create(array $attributes)
 * @method Season|null find(int $id, array|string $columns = ['*'])
 * @method Season findOrFail(int $id, array|string $columns = ['*'])
 * @method Season findOrNew(int $id, array|string $columns = ['*'])
 * @method bool save(Season $model)
 * @method bool update(Season $model, array $attributes = [], array $options = [])
 * @method bool delete(Season $model)
 * @method Collection<Season> all()
 */
interface SeasonRepositoryInterface extends BaseRepositoryInterface
{

}
