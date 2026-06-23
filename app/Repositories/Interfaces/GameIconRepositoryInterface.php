<?php

namespace App\Repositories\Interfaces;

use App\Models\GameIcon;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method GameIcon                  create(array<string, mixed> $attributes)
 * @method GameIcon|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method GameIcon                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method GameIcon                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                      save(GameIcon $model)
 * @method bool                      update(GameIcon $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                      delete(GameIcon $model)
 * @method Collection<int, GameIcon> all()
 * @method bool                      exists(array<int, string> $columns)
 */
interface GameIconRepositoryInterface extends BaseRepositoryInterface
{
}
