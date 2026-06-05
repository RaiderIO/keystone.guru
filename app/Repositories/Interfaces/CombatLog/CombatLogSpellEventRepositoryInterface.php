<?php

namespace App\Repositories\Interfaces\CombatLog;

use App\Models\CombatLog\CombatLogSpellEvent;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method CombatLogSpellEvent             create(array $attributes)
 * @method CombatLogSpellEvent|null        find(int $id, array|string $columns = ['*'])
 * @method CombatLogSpellEvent             findOrFail(int $id, array|string $columns = ['*'])
 * @method CombatLogSpellEvent             findOrNew(int $id, array|string $columns = ['*'])
 * @method bool                            save(CombatLogSpellEvent $model)
 * @method bool                            update(CombatLogSpellEvent $model, array $attributes = [], array $options = [])
 * @method bool                            delete(CombatLogSpellEvent $model)
 * @method Collection<CombatLogSpellEvent> all()
 * @method bool                            exists(array $columns)
 */
interface CombatLogSpellEventRepositoryInterface extends BaseRepositoryInterface
{
}
