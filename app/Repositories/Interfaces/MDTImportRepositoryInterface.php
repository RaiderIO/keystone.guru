<?php

namespace App\Repositories\Interfaces;

use App\Models\MDTImport;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method MDTImport                  create(array<string, mixed> $attributes)
 * @method MDTImport|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method MDTImport                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method MDTImport                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                       save(MDTImport $model)
 * @method bool                       update(MDTImport $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                       delete(MDTImport $model)
 * @method Collection<int, MDTImport> all()
 * @method bool                       exists(array<int, string> $columns)
 */
interface MDTImportRepositoryInterface extends BaseRepositoryInterface
{
}
