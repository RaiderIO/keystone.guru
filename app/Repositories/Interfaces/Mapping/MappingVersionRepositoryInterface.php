<?php

namespace App\Repositories\Interfaces\Mapping;

use App\Models\Mapping\MappingVersion;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method MappingVersion                  create(array<string, mixed> $attributes)
 * @method MappingVersion|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method MappingVersion                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method MappingVersion                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                            save(MappingVersion $model)
 * @method bool                            update(MappingVersion $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                            delete(MappingVersion $model)
 * @method Collection<int, MappingVersion> all()
 * @method bool                            exists(array<int, string> $columns)
 */
interface MappingVersionRepositoryInterface extends BaseRepositoryInterface
{
}
