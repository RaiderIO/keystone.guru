<?php

namespace App\Repositories\Interfaces\Mapping;

use App\Models\Mapping\MappingChangeLog;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method MappingChangeLog create(array $attributes)
 * @method MappingChangeLog|null find(int $id, array|string $columns = ['*'])
 * @method MappingChangeLog findOrFail(int $id, array|string $columns = ['*'])
 * @method MappingChangeLog findOrNew(int $id, array|string $columns = ['*'])
 * @method bool save(MappingChangeLog $model)
 * @method bool update(MappingChangeLog $model, array $attributes = [], array $options = [])
 * @method bool delete(MappingChangeLog $model)
 * @method Collection<MappingChangeLog> all()
 */
interface MappingChangeLogRepositoryInterface extends BaseRepositoryInterface
{

}
