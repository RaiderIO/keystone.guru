<?php

namespace App\Repositories\Interfaces\Floor;

use App\Models\Floor\Floor;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method Floor                  create(array<string, mixed> $attributes)
 * @method Floor|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method Floor                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method Floor                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                   save(Floor $model)
 * @method bool                   update(Floor $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                   delete(Floor $model)
 * @method Collection<int, Floor> all()
 * @method bool                   exists(array<string, mixed> $columns)
 */
interface FloorRepositoryInterface extends BaseRepositoryInterface
{
    public function findByUiMapId(int $uiMapId, ?int $dungeonId = null): ?Floor;

    public function getDefaultFloorForDungeon(int $dungeonId): ?Floor;
}
