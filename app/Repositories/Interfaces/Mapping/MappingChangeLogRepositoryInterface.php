<?php

namespace App\Repositories\Interfaces\Mapping;

use App\Models\Mapping\MappingChangeLog;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method MappingChangeLog create(array $attributes)
 * @method MappingChangeLog find(int $id, array $columns = [])
 * @method MappingChangeLog findOrFail(int $id, array $columns = [])
 * @method MappingChangeLog findOrNew(int $id, array $columns = [])
 * @method bool save(MappingChangeLog $model)
 * @method bool update(MappingChangeLog $model, array $attributes = [], array $options = [])
 * @method bool delete(MappingChangeLog $model)
 * @method Collection<MappingChangeLog> all()
 */
interface MappingChangeLogRepositoryInterface extends BaseRepositoryInterface
{

}
