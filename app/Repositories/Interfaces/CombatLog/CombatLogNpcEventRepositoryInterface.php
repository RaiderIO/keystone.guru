<?php

namespace App\Repositories\Interfaces\CombatLog;

use App\Models\CombatLog\CombatLogNpcEvent;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method CombatLogNpcEvent                  create(array<string, mixed> $attributes)
 * @method CombatLogNpcEvent|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method CombatLogNpcEvent                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method CombatLogNpcEvent                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                               save(CombatLogNpcEvent $model)
 * @method bool                               update(CombatLogNpcEvent $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                               delete(CombatLogNpcEvent $model)
 * @method Collection<int, CombatLogNpcEvent> all()
 * @method bool                               exists(array<int, string> $columns)
 */
interface CombatLogNpcEventRepositoryInterface extends BaseRepositoryInterface
{
}
