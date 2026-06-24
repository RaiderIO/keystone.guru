<?php

namespace App\Repositories\Interfaces\KillZone;

use App\Models\KillZone\KillZone;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method KillZone                  create(array<string, mixed> $attributes)
 * @method KillZone|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method KillZone                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method KillZone                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                      save(KillZone $model)
 * @method bool                      update(KillZone $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                      delete(KillZone $model)
 * @method Collection<int, KillZone> all()
 * @method bool                      exists(array<int, string> $columns)
 */
interface KillZoneRepositoryInterface extends BaseRepositoryInterface
{
}
