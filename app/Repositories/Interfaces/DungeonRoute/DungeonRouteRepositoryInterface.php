<?php

namespace App\Repositories\Interfaces\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method DungeonRoute create(array $attributes)
 * @method DungeonRoute|null find(int $id, array|string $columns = ['*'])
 * @method DungeonRoute findOrFail(int $id, array|string $columns = ['*'])
 * @method DungeonRoute findOrNew(int $id, array|string $columns = ['*'])
 * @method bool save(DungeonRoute $model)
 * @method bool update(DungeonRoute $model, array $attributes = [], array $options = [])
 * @method bool delete(DungeonRoute $model)
 * @method Collection<DungeonRoute> all()
 */
interface DungeonRouteRepositoryInterface extends BaseRepositoryInterface
{
    public function generateRandomPublicKey(): string;

    public function getDungeonRoutesWithExpiredThumbnails(?Collection $dungeonRoutes = null): Collection;
}
