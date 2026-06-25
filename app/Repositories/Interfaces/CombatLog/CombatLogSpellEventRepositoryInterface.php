<?php

namespace App\Repositories\Interfaces\CombatLog;

use App\Models\CombatLog\CombatLogSpellEvent;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method CombatLogSpellEvent                  create(array<string, mixed> $attributes)
 * @method CombatLogSpellEvent|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method CombatLogSpellEvent                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method CombatLogSpellEvent                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                 save(CombatLogSpellEvent $model)
 * @method bool                                 update(CombatLogSpellEvent $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                 delete(CombatLogSpellEvent $model)
 * @method Collection<int, CombatLogSpellEvent> all()
 * @method bool                                 exists(array<int, string> $columns)
 */
interface CombatLogSpellEventRepositoryInterface extends BaseRepositoryInterface
{
}
