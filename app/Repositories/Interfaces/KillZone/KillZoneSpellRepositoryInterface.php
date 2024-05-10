<?php

namespace App\Repositories\Interfaces\KillZone;

use App\Models\KillZone\KillZoneSpell;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method KillZoneSpell create(array $attributes)
 * @method KillZoneSpell find(int $id, array $columns = [])
 * @method KillZoneSpell findOrFail(int $id, array $columns = [])
 * @method KillZoneSpell findOrNew(int $id, array $columns = [])
 * @method bool save(KillZoneSpell $model)
 * @method bool update(KillZoneSpell $model, array $attributes = [], array $options = [])
 * @method bool delete(KillZoneSpell $model)
 * @method Collection<KillZoneSpell> all()
 */
interface KillZoneSpellRepositoryInterface extends BaseRepositoryInterface
{

}
