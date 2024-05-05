<?php

namespace App\Repositories\Interfaces\CombatLog;

use App\Models\CombatLog\ChallengeModeRun;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method ChallengeModeRun create(array $attributes)
 * @method ChallengeModeRun find(int $id, array $columns = [])
 * @method ChallengeModeRun findOrFail(int $id, array $columns = [])
 * @method ChallengeModeRun findOrNew(int $id, array $columns = [])
 * @method bool save(ChallengeModeRun $model)
 * @method bool update(ChallengeModeRun $model, array $attributes = [], array $options = [])
 * @method bool delete(ChallengeModeRun $model)
 * @method Collection<ChallengeModeRun> all()
 */
interface ChallengeModeRunRepositoryInterface extends BaseRepositoryInterface
{

}
