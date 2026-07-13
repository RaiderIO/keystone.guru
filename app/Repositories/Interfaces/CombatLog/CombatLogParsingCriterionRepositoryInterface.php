<?php

namespace App\Repositories\Interfaces\CombatLog;

use App\Models\CombatLog\CombatLogParsingCriterion;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method CombatLogParsingCriterion                  create(array<string, mixed> $attributes)
 * @method CombatLogParsingCriterion|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method CombatLogParsingCriterion                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method CombatLogParsingCriterion                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                       save(CombatLogParsingCriterion $model)
 * @method bool                                       update(CombatLogParsingCriterion $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                       delete(CombatLogParsingCriterion $model)
 * @method Collection<int, CombatLogParsingCriterion> all()
 * @method bool                                       exists(array<int, string> $columns)
 */
interface CombatLogParsingCriterionRepositoryInterface extends BaseRepositoryInterface
{
}
