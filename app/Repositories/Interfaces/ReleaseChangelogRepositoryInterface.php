<?php

namespace App\Repositories\Interfaces;

use App\Models\ReleaseChangelog;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method ReleaseChangelog create(array $attributes)
 * @method ReleaseChangelog find(int $id, array $columns = [])
 * @method ReleaseChangelog findOrFail(int $id, array $columns = [])
 * @method ReleaseChangelog findOrNew(int $id, array $columns = [])
 * @method bool save(ReleaseChangelog $model)
 * @method bool update(ReleaseChangelog $model, array $attributes = [], array $options = [])
 * @method bool delete(ReleaseChangelog $model)
 * @method Collection<ReleaseChangelog> all()
 */
interface ReleaseChangelogRepositoryInterface extends BaseRepositoryInterface
{

}
