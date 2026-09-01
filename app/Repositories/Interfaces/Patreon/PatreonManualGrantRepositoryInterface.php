<?php

namespace App\Repositories\Interfaces\Patreon;

use App\Models\Patreon\PatreonManualGrant;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * @method PatreonManualGrant                  create(array<string, mixed> $attributes)
 * @method PatreonManualGrant|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method PatreonManualGrant                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method PatreonManualGrant                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                save(PatreonManualGrant $model)
 * @method bool                                update(PatreonManualGrant $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                delete(PatreonManualGrant $model)
 * @method Collection<int, PatreonManualGrant> all()
 * @method bool                                exists(array<int, string> $columns)
 */
interface PatreonManualGrantRepositoryInterface extends BaseRepositoryInterface
{
    public function hasActiveGrantForUserId(int $userId): bool;

    public function getActiveGrantForUserId(int $userId): ?PatreonManualGrant;

    /**
     * All grants that have not been revoked, most recently granted first.
     *
     * @return EloquentCollection<int, PatreonManualGrant>
     */
    public function getActiveGrants(): EloquentCollection;
}
