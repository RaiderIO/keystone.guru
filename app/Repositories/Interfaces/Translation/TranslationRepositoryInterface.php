<?php

namespace App\Repositories\Interfaces\Translation;

use App\Models\Translation\Translation;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method Translation             create(array $attributes)
 * @method Translation|null        find(int $id, array|string $columns = ['*'])
 * @method Translation             findOrFail(int $id, array|string $columns = ['*'])
 * @method Translation             findOrNew(int $id, array|string $columns = ['*'])
 * @method bool                    save(Translation $model)
 * @method bool                    update(Translation $model, array $attributes = [], array $options = [])
 * @method bool                    delete(Translation $model)
 * @method Collection<Translation> all()
 * @method bool                    exists(array $columns)
 */
interface TranslationRepositoryInterface extends BaseRepositoryInterface
{
}
