<?php

namespace App\Repositories\Interfaces;

use App\Models\ReleaseChangelogCategory;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method ReleaseChangelogCategory                  create(array<string, mixed> $attributes)
 * @method ReleaseChangelogCategory|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method ReleaseChangelogCategory                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method ReleaseChangelogCategory                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                      save(ReleaseChangelogCategory $model)
 * @method bool                                      update(ReleaseChangelogCategory $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                      delete(ReleaseChangelogCategory $model)
 * @method Collection<int, ReleaseChangelogCategory> all()
 * @method bool                                      exists(array<int, string> $columns)
 */
interface ReleaseChangelogCategoryRepositoryInterface extends BaseRepositoryInterface
{
}
