<?php

namespace App\Repositories\Interfaces;

use App\Models\ReleaseChangelog;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method ReleaseChangelog                  create(array<string, mixed> $attributes)
 * @method ReleaseChangelog|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method ReleaseChangelog                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method ReleaseChangelog                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                              save(ReleaseChangelog $model)
 * @method bool                              update(ReleaseChangelog $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                              delete(ReleaseChangelog $model)
 * @method Collection<int, ReleaseChangelog> all()
 * @method bool                              exists(array<int, string> $columns)
 */
interface ReleaseChangelogRepositoryInterface extends BaseRepositoryInterface
{
}
