<?php

namespace App\Repositories\Interfaces;

use App\Models\Characteristic;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method Characteristic                  create(array<string, mixed> $attributes)
 * @method Characteristic|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method Characteristic                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method Characteristic                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                            save(Characteristic $model)
 * @method bool                            update(Characteristic $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                            delete(Characteristic $model)
 * @method Collection<int, Characteristic> all()
 * @method bool                            exists(array<int, string> $columns)
 */
interface CharacteristicRepositoryInterface extends BaseRepositoryInterface
{
}
