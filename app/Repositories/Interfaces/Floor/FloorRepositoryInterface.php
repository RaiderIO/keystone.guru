<?php

namespace App\Repositories\Interfaces\Floor;

use App\Models\Floor\Floor;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method Floor             create(array $attributes)
 * @method Floor|null        find(int $id, array|string $columns = ['*'])
 * @method Floor             findOrFail(int $id, array|string $columns = ['*'])
 * @method Floor             findOrNew(int $id, array|string $columns = ['*'])
 * @method bool              save(Floor $model)
 * @method bool              update(Floor $model, array $attributes = [], array $options = [])
 * @method bool              delete(Floor $model)
 * @method Collection<Floor> all()
 */
interface FloorRepositoryInterface extends BaseRepositoryInterface
{
    public function findByUiMapId(int $uiMapId, ?int $dungeonId = null): ?Floor;

    public function getDefaultFloorForDungeon(int $dungeonId): ?Floor;
}
