<?php

namespace App\Repositories\Interfaces\KillZone;

use App\Models\KillZone\KillZoneEnemy;
use App\Repositories\BaseRepositoryInterface;

/**
 * @method KillZoneEnemy create(array $attributes)
 * @method KillZoneEnemy find(int $id)
 * @method bool save(KillZoneEnemy $dungeonRoute)
 */
interface KillZoneEnemyRepositoryInterface extends BaseRepositoryInterface
{

}
