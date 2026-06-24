<?php

namespace App\Repositories\Interfaces\Mapping;

use App\Models\Mapping\MappingChangeLog;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method MappingChangeLog                  create(array<string, mixed> $attributes)
 * @method MappingChangeLog|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method MappingChangeLog                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method MappingChangeLog                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                              save(MappingChangeLog $model)
 * @method bool                              update(MappingChangeLog $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                              delete(MappingChangeLog $model)
 * @method Collection<int, MappingChangeLog> all()
 * @method bool                              exists(array<int, string> $columns)
 */
interface MappingChangeLogRepositoryInterface extends BaseRepositoryInterface
{
}
