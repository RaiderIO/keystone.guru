<?php

namespace App\Repositories\Interfaces;

use App\Models\MDTImport;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method MDTImport create(array $attributes)
 * @method MDTImport|null find(int $id, array|string $columns = ['*'])
 * @method MDTImport findOrFail(int $id, array|string $columns = ['*'])
 * @method MDTImport findOrNew(int $id, array|string $columns = ['*'])
 * @method bool save(MDTImport $model)
 * @method bool update(MDTImport $model, array $attributes = [], array $options = [])
 * @method bool delete(MDTImport $model)
 * @method Collection<MDTImport> all()
 */
interface MDTImportRepositoryInterface extends BaseRepositoryInterface
{

}
