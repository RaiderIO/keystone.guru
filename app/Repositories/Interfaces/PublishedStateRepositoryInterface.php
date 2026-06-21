<?php

namespace App\Repositories\Interfaces;

use App\Models\PublishedState;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method PublishedState                  create(array<string, mixed> $attributes)
 * @method PublishedState|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method PublishedState                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method PublishedState                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                            save(PublishedState $model)
 * @method bool                            update(PublishedState $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                            delete(PublishedState $model)
 * @method Collection<int, PublishedState> all()
 * @method bool                            exists(array<int, string> $columns)
 */
interface PublishedStateRepositoryInterface extends BaseRepositoryInterface
{
}
