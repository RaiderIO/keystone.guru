<?php

namespace App\Repositories\Interfaces\KillZone;

use App\Models\KillZone\KillZone;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method KillZone create(array $attributes)
 * @method KillZone find(int $id, array $columns = [])
 * @method KillZone findOrFail(int $id, array $columns = [])
 * @method KillZone findOrNew(int $id, array $columns = [])
 * @method bool save(KillZone $model)
 * @method bool update(KillZone $model, array $attributes = [], array $options = [])
 * @method bool delete(KillZone $model)
 * @method Collection<KillZone> all()
 */
interface KillZoneRepositoryInterface extends BaseRepositoryInterface
{

}
