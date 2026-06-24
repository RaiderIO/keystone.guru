<?php

namespace App\Repositories\Interfaces;

use App\Models\ReleaseChangelogChange;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method ReleaseChangelogChange                  create(array<string, mixed> $attributes)
 * @method ReleaseChangelogChange|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method ReleaseChangelogChange                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method ReleaseChangelogChange                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                    save(ReleaseChangelogChange $model)
 * @method bool                                    update(ReleaseChangelogChange $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                    delete(ReleaseChangelogChange $model)
 * @method Collection<int, ReleaseChangelogChange> all()
 * @method bool                                    exists(array<int, string> $columns)
 */
interface ReleaseChangelogChangeRepositoryInterface extends BaseRepositoryInterface
{
}
