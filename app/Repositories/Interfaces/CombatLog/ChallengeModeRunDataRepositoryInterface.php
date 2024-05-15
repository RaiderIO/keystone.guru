<?php

namespace App\Repositories\Interfaces\CombatLog;

use App\Models\CombatLog\ChallengeModeRunData;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method ChallengeModeRunData create(array $attributes)
 * @method ChallengeModeRunData|null find(int $id, array|string $columns = ['*'])
 * @method ChallengeModeRunData findOrFail(int $id, array|string $columns = ['*'])
 * @method ChallengeModeRunData findOrNew(int $id, array|string $columns = ['*'])
 * @method bool save(ChallengeModeRunData $model)
 * @method bool update(ChallengeModeRunData $model, array $attributes = [], array $options = [])
 * @method bool delete(ChallengeModeRunData $model)
 * @method Collection<ChallengeModeRunData> all()
 */
interface ChallengeModeRunDataRepositoryInterface extends BaseRepositoryInterface
{

}
