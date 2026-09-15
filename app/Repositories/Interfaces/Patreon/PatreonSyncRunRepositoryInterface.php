<?php

namespace App\Repositories\Interfaces\Patreon;

use App\Models\Patreon\PatreonSyncRun;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method PatreonSyncRun                  create(array<string, mixed> $attributes)
 * @method PatreonSyncRun|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method PatreonSyncRun                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method PatreonSyncRun                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                            save(PatreonSyncRun $model)
 * @method bool                            update(PatreonSyncRun $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                            delete(PatreonSyncRun $model)
 * @method Collection<int, PatreonSyncRun> all()
 * @method bool                            exists(array<int, string> $columns)
 */
interface PatreonSyncRunRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * The most recent runs, newest first.
     *
     * @return Collection<int, PatreonSyncRun>
     */
    public function getMostRecent(int $limit): Collection;
}
