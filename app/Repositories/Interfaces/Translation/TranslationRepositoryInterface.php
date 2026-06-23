<?php

namespace App\Repositories\Interfaces\Translation;

use App\Models\Translation\Translation;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method Translation                  create(array<string, mixed> $attributes)
 * @method Translation|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method Translation                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method Translation                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                         save(Translation $model)
 * @method bool                         update(Translation $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                         delete(Translation $model)
 * @method Collection<int, Translation> all()
 * @method bool                         exists(array<int, string> $columns)
 */
interface TranslationRepositoryInterface extends BaseRepositoryInterface
{
}
