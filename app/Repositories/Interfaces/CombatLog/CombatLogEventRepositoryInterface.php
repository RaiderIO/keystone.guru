<?php

namespace App\Repositories\Interfaces\CombatLog;

use App\Models\CombatLog\CombatLogEvent;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method CombatLogEvent create(array $attributes)
 * @method CombatLogEvent find(int $id, array $columns = [])
 * @method CombatLogEvent findOrFail(int $id, array $columns = [])
 * @method CombatLogEvent findOrNew(int $id, array $columns = [])
 * @method bool save(CombatLogEvent $model)
 * @method bool update(CombatLogEvent $model, array $attributes = [], array $options = [])
 * @method bool delete(CombatLogEvent $model)
 * @method Collection<CombatLogEvent> all()
 */
interface CombatLogEventRepositoryInterface extends BaseRepositoryInterface
{

}
