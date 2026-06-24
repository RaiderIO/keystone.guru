<?php

namespace App\Repositories\Interfaces\Opensearch;

use App\Models\Opensearch\OpensearchModel;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method OpensearchModel                  create(array<string, mixed> $attributes)
 * @method OpensearchModel|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method OpensearchModel                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method OpensearchModel                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                             save(OpensearchModel $model)
 * @method bool                             update(OpensearchModel $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                             delete(OpensearchModel $model)
 * @method Collection<int, OpensearchModel> all()
 * @method bool                             exists(array<int, string> $columns)
 */
interface OpensearchModelRepositoryInterface extends BaseRepositoryInterface
{
}
