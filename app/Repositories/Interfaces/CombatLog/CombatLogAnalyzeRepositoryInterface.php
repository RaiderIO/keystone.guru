<?php

namespace App\Repositories\Interfaces\CombatLog;

use App\Models\CombatLog\CombatLogAnalyze;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method CombatLogAnalyze             create(array $attributes)
 * @method CombatLogAnalyze|null        find(int $id, array|string $columns = ['*'])
 * @method CombatLogAnalyze             findOrFail(int $id, array|string $columns = ['*'])
 * @method CombatLogAnalyze             findOrNew(int $id, array|string $columns = ['*'])
 * @method bool                         save(CombatLogAnalyze $model)
 * @method bool                         update(CombatLogAnalyze $model, array $attributes = [], array $options = [])
 * @method bool                         delete(CombatLogAnalyze $model)
 * @method Collection<CombatLogAnalyze> all()
 * @method bool                         exists(array $columns)
 */
interface CombatLogAnalyzeRepositoryInterface extends BaseRepositoryInterface
{
}
