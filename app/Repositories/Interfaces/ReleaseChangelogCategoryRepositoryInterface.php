<?php

namespace App\Repositories\Interfaces;

use App\Models\ReleaseChangelogCategory;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method ReleaseChangelogCategory create(array $attributes)
 * @method ReleaseChangelogCategory find(int $id, array $columns = [])
 * @method ReleaseChangelogCategory findOrFail(int $id, array $columns = [])
 * @method ReleaseChangelogCategory findOrNew(int $id, array $columns = [])
 * @method bool save(ReleaseChangelogCategory $model)
 * @method bool update(ReleaseChangelogCategory $model, array $attributes = [], array $options = [])
 * @method bool delete(ReleaseChangelogCategory $model)
 * @method Collection<ReleaseChangelogCategory> all()
 */
interface ReleaseChangelogCategoryRepositoryInterface extends BaseRepositoryInterface
{

}
