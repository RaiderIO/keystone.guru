<?php

namespace App\Repositories\Interfaces;

use App\Models\Affix;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method Affix                  create(array<string, mixed> $attributes)
 * @method Affix|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method Affix                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method Affix                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                   save(Affix $model)
 * @method bool                   update(Affix $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                   delete(Affix $model)
 * @method Collection<int, Affix> all()
 * @method bool                   exists(array<string, mixed> $columns)
 */
interface AffixRepositoryInterface extends BaseRepositoryInterface
{
}
