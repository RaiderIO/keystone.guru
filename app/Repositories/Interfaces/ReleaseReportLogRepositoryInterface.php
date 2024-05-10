<?php

namespace App\Repositories\Interfaces;

use App\Models\ReleaseReportLog;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method ReleaseReportLog create(array $attributes)
 * @method ReleaseReportLog find(int $id, array $columns = [])
 * @method ReleaseReportLog findOrFail(int $id, array $columns = [])
 * @method ReleaseReportLog findOrNew(int $id, array $columns = [])
 * @method bool save(ReleaseReportLog $model)
 * @method bool update(ReleaseReportLog $model, array $attributes = [], array $options = [])
 * @method bool delete(ReleaseReportLog $model)
 * @method Collection<ReleaseReportLog> all()
 */
interface ReleaseReportLogRepositoryInterface extends BaseRepositoryInterface
{

}
