<?php

namespace App\Repositories\Interfaces;

use App\Models\SeasonDungeon;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method SeasonDungeon                  create(array<string, mixed> $attributes)
 * @method SeasonDungeon|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method SeasonDungeon                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method SeasonDungeon                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                           save(SeasonDungeon $model)
 * @method bool                           update(SeasonDungeon $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                           delete(SeasonDungeon $model)
 * @method Collection<int, SeasonDungeon> all()
 * @method bool                           exists(array<int, string> $columns)
 */
interface SeasonDungeonRepositoryInterface extends BaseRepositoryInterface
{
}
