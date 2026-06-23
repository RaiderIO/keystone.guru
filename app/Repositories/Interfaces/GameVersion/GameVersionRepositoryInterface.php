<?php

namespace App\Repositories\Interfaces\GameVersion;

use App\Models\GameVersion\GameVersion;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method GameVersion                  create(array<string, mixed> $attributes)
 * @method GameVersion|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method GameVersion                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method GameVersion                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                         save(GameVersion $model)
 * @method bool                         update(GameVersion $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                         delete(GameVersion $model)
 * @method Collection<int, GameVersion> all()
 * @method bool                         exists(array<int, string> $columns)
 */
interface GameVersionRepositoryInterface extends BaseRepositoryInterface
{
}
