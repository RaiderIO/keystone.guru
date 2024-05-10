<?php

namespace App\Repositories\Interfaces;

use App\Models\ReleaseChangelogChange;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method ReleaseChangelogChange create(array $attributes)
 * @method ReleaseChangelogChange find(int $id, array $columns = [])
 * @method ReleaseChangelogChange findOrFail(int $id, array $columns = [])
 * @method ReleaseChangelogChange findOrNew(int $id, array $columns = [])
 * @method bool save(ReleaseChangelogChange $model)
 * @method bool update(ReleaseChangelogChange $model, array $attributes = [], array $options = [])
 * @method bool delete(ReleaseChangelogChange $model)
 * @method Collection<ReleaseChangelogChange> all()
 */
interface ReleaseChangelogChangeRepositoryInterface extends BaseRepositoryInterface
{

}
