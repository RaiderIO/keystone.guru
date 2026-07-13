<?php

namespace App\Repositories\Interfaces;

use App\Models\UserReport;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method UserReport                  create(array<string, mixed> $attributes)
 * @method UserReport|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method UserReport                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method UserReport                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                        save(UserReport $model)
 * @method bool                        update(UserReport $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                        delete(UserReport $model)
 * @method Collection<int, UserReport> all()
 * @method bool                        exists(array<int, string> $columns)
 */
interface UserReportRepositoryInterface extends BaseRepositoryInterface
{
}
