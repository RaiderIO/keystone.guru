<?php

namespace App\Repositories\Interfaces\Opensearch;

use App\Models\Opensearch\OpensearchModel;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method OpensearchModel create(array $attributes)
 * @method OpensearchModel|null find(int $id, array|string $columns = ['*'])
 * @method OpensearchModel findOrFail(int $id, array|string $columns = ['*'])
 * @method OpensearchModel findOrNew(int $id, array|string $columns = ['*'])
 * @method bool save(OpensearchModel $model)
 * @method bool update(OpensearchModel $model, array $attributes = [], array $options = [])
 * @method bool delete(OpensearchModel $model)
 * @method Collection<OpensearchModel> all()
 */
interface OpensearchModelRepositoryInterface extends BaseRepositoryInterface
{

}
