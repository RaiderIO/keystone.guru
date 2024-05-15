<?php

namespace App\Repositories\Interfaces;

use App\Models\SeasonDungeon;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method SeasonDungeon create(array $attributes)
 * @method SeasonDungeon|null find(int $id, array|string $columns = ['*'])
 * @method SeasonDungeon findOrFail(int $id, array|string $columns = ['*'])
 * @method SeasonDungeon findOrNew(int $id, array|string $columns = ['*'])
 * @method bool save(SeasonDungeon $model)
 * @method bool update(SeasonDungeon $model, array $attributes = [], array $options = [])
 * @method bool delete(SeasonDungeon $model)
 * @method Collection<SeasonDungeon> all()
 */
interface SeasonDungeonRepositoryInterface extends BaseRepositoryInterface
{

}
