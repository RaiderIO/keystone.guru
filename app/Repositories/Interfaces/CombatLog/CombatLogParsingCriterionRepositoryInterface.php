<?php

namespace App\Repositories\Interfaces\CombatLog;

use App\Models\CombatLog\CombatLogParsingCriterion;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method CombatLogParsingCriterion             create(array $attributes)
 * @method CombatLogParsingCriterion|null        find(int $id, array|string $columns = ['*'])
 * @method CombatLogParsingCriterion             findOrFail(int $id, array|string $columns = ['*'])
 * @method CombatLogParsingCriterion             findOrNew(int $id, array|string $columns = ['*'])
 * @method bool                                  save(CombatLogParsingCriterion $model)
 * @method bool                                  update(CombatLogParsingCriterion $model, array $attributes = [], array $options = [])
 * @method bool                                  delete(CombatLogParsingCriterion $model)
 * @method Collection<CombatLogParsingCriterion> all()
 * @method bool                                  exists(array $columns)
 */
interface CombatLogParsingCriterionRepositoryInterface extends BaseRepositoryInterface
{
}
