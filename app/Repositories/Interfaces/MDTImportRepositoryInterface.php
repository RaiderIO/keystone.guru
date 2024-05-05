<?php

namespace App\Repositories\Interfaces;

use App\Models\MDTImport;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method MDTImport create(array $attributes)
 * @method MDTImport find(int $id, array $columns = [])
 * @method MDTImport findOrFail(int $id, array $columns = [])
 * @method MDTImport findOrNew(int $id, array $columns = [])
 * @method bool save(MDTImport $model)
 * @method bool update(MDTImport $model, array $attributes = [], array $options = [])
 * @method bool delete(MDTImport $model)
 * @method Collection<MDTImport> all()
 */
interface MDTImportRepositoryInterface extends BaseRepositoryInterface
{

}
