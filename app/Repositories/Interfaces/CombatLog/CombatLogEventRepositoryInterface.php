<?php

namespace App\Repositories\Interfaces\CombatLog;

use App\Models\CombatLog\CombatLogEvent;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method CombatLogEvent                  create(array<string, mixed> $attributes)
 * @method CombatLogEvent|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method CombatLogEvent                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method CombatLogEvent                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                            save(CombatLogEvent $model)
 * @method bool                            update(CombatLogEvent $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                            delete(CombatLogEvent $model)
 * @method Collection<int, CombatLogEvent> all()
 * @method bool                            exists(array<int, string> $columns)
 */
interface CombatLogEventRepositoryInterface extends BaseRepositoryInterface
{
}
