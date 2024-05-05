<?php

namespace App\Repositories\Interfaces\Mapping;

use App\Models\Mapping\MappingVersion;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method MappingVersion create(array $attributes)
 * @method MappingVersion find(int $id, array $columns = [])
 * @method MappingVersion findOrFail(int $id, array $columns = [])
 * @method MappingVersion findOrNew(int $id, array $columns = [])
 * @method bool save(MappingVersion $model)
 * @method bool update(MappingVersion $model, array $attributes = [], array $options = [])
 * @method bool delete(MappingVersion $model)
 * @method Collection<MappingVersion> all()
 */
interface MappingVersionRepositoryInterface extends BaseRepositoryInterface
{

}
