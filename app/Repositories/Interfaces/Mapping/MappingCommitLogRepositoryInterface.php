<?php

namespace App\Repositories\Interfaces\Mapping;

use App\Models\Mapping\MappingCommitLog;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method MappingCommitLog create(array $attributes)
 * @method MappingCommitLog|null find(int $id, array|string $columns = ['*'])
 * @method MappingCommitLog findOrFail(int $id, array|string $columns = ['*'])
 * @method MappingCommitLog findOrNew(int $id, array|string $columns = ['*'])
 * @method bool save(MappingCommitLog $model)
 * @method bool update(MappingCommitLog $model, array $attributes = [], array $options = [])
 * @method bool delete(MappingCommitLog $model)
 * @method Collection<MappingCommitLog> all()
 */
interface MappingCommitLogRepositoryInterface extends BaseRepositoryInterface
{

}
