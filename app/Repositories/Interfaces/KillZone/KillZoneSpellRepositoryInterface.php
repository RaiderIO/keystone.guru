<?php

namespace App\Repositories\Interfaces\KillZone;

use App\Models\KillZone\KillZoneSpell;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method KillZoneSpell                  create(array<string, mixed> $attributes)
 * @method KillZoneSpell|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method KillZoneSpell                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method KillZoneSpell                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                           save(KillZoneSpell $model)
 * @method bool                           update(KillZoneSpell $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                           delete(KillZoneSpell $model)
 * @method Collection<int, KillZoneSpell> all()
 * @method bool                           exists(array<int, string> $columns)
 */
interface KillZoneSpellRepositoryInterface extends BaseRepositoryInterface
{
}
