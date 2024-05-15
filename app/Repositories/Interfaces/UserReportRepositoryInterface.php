<?php

namespace App\Repositories\Interfaces;

use App\Models\UserReport;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method UserReport create(array $attributes)
 * @method UserReport|null find(int $id, array|string $columns = ['*'])
 * @method UserReport findOrFail(int $id, array|string $columns = ['*'])
 * @method UserReport findOrNew(int $id, array|string $columns = ['*'])
 * @method bool save(UserReport $model)
 * @method bool update(UserReport $model, array $attributes = [], array $options = [])
 * @method bool delete(UserReport $model)
 * @method Collection<UserReport> all()
 */
interface UserReportRepositoryInterface extends BaseRepositoryInterface
{

}
