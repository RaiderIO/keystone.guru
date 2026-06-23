<?php

namespace App\Repositories\Interfaces\CombatLog;

use App\Models\CombatLog\ChallengeModeRun;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method ChallengeModeRun                  create(array<string, mixed> $attributes)
 * @method ChallengeModeRun|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method ChallengeModeRun                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method ChallengeModeRun                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                              save(ChallengeModeRun $model)
 * @method bool                              update(ChallengeModeRun $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                              delete(ChallengeModeRun $model)
 * @method Collection<int, ChallengeModeRun> all()
 * @method bool                              exists(array<int, string> $columns)
 */
interface ChallengeModeRunRepositoryInterface extends BaseRepositoryInterface
{
}
