<?php

namespace App\Repositories\Interfaces\CombatLog;

use App\Models\CombatLog\CombatLogAnalyze;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method CombatLogAnalyze                  create(array<string, mixed> $attributes)
 * @method CombatLogAnalyze|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method CombatLogAnalyze                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method CombatLogAnalyze                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                              save(CombatLogAnalyze $model)
 * @method bool                              update(CombatLogAnalyze $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                              delete(CombatLogAnalyze $model)
 * @method Collection<int, CombatLogAnalyze> all()
 * @method bool                              exists(array<int, string> $columns)
 */
interface CombatLogAnalyzeRepositoryInterface extends BaseRepositoryInterface
{
}
