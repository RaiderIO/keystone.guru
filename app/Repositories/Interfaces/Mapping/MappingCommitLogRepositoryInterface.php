<?php

namespace App\Repositories\Interfaces\Mapping;

use App\Models\Mapping\MappingCommitLog;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method MappingCommitLog                  create(array<string, mixed> $attributes)
 * @method MappingCommitLog|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method MappingCommitLog                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method MappingCommitLog                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                              save(MappingCommitLog $model)
 * @method bool                              update(MappingCommitLog $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                              delete(MappingCommitLog $model)
 * @method Collection<int, MappingCommitLog> all()
 * @method bool                              exists(array<int, string> $columns)
 */
interface MappingCommitLogRepositoryInterface extends BaseRepositoryInterface
{
}
