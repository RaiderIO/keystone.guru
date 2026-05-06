<?php

namespace App\Repositories\Interfaces\KillZone;

use App\Models\KillZone\KillZoneSpell;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method KillZoneSpell             create(array $attributes)
 * @method KillZoneSpell|null        find(int $id, array|string $columns = ['*'])
 * @method KillZoneSpell             findOrFail(int $id, array|string $columns = ['*'])
 * @method KillZoneSpell             findOrNew(int $id, array|string $columns = ['*'])
 * @method bool                      save(KillZoneSpell $model)
 * @method bool                      update(KillZoneSpell $model, array $attributes = [], array $options = [])
 * @method bool                      delete(KillZoneSpell $model)
 * @method Collection<KillZoneSpell> all()
 */
interface KillZoneSpellRepositoryInterface extends BaseRepositoryInterface
{
}
