<?php

namespace App\Repositories\Interfaces;

use App\Models\ReleaseReportLog;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method ReleaseReportLog                  create(array<string, mixed> $attributes)
 * @method ReleaseReportLog|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method ReleaseReportLog                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method ReleaseReportLog                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                              save(ReleaseReportLog $model)
 * @method bool                              update(ReleaseReportLog $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                              delete(ReleaseReportLog $model)
 * @method Collection<int, ReleaseReportLog> all()
 * @method bool                              exists(array<int, string> $columns)
 */
interface ReleaseReportLogRepositoryInterface extends BaseRepositoryInterface
{
    public function hasReportedVersionOnPlatform(string $version, string $platform): bool;
}
