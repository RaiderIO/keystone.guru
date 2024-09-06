<?php

namespace App\Repositories\Interfaces;

use App\Models\File;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method File create(array $attributes)
 * @method File|null find(int $id, array|string $columns = ['*'])
 * @method File findOrFail(int $id, array|string $columns = ['*'])
 * @method File findOrNew(int $id, array|string $columns = ['*'])
 * @method bool save(File $model)
 * @method bool update(File $model, array $attributes = [], array $options = [])
 * @method bool delete(File $model)
 * @method Collection<File> all()
 */
interface FileRepositoryInterface extends BaseRepositoryInterface
{

}
