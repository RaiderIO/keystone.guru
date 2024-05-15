<?php

namespace App\Repositories\Interfaces\KillZone;

use App\Models\KillZone\KillZoneEnemy;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method KillZoneEnemy create(array $attributes)
 * @method KillZoneEnemy find(int $id, array $columns = [])
 * @method KillZoneEnemy findOrFail(int $id, array $columns = [])
 * @method KillZoneEnemy findOrNew(int $id, array $columns = [])
 * @method bool save(KillZoneEnemy $model)
 * @method bool update(KillZoneEnemy $model, array $attributes = [], array $options = [])
 * @method bool delete(KillZoneEnemy $model)
 * @method Collection<KillZoneEnemy> all()
 */
interface KillZoneEnemyRepositoryInterface extends BaseRepositoryInterface
{

}
