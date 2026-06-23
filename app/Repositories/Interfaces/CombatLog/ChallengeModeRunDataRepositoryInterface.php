<?php

namespace App\Repositories\Interfaces\CombatLog;

use App\Models\CombatLog\ChallengeModeRunData;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method ChallengeModeRunData                  create(array<string, mixed> $attributes)
 * @method ChallengeModeRunData|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method ChallengeModeRunData                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method ChallengeModeRunData                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                  save(ChallengeModeRunData $model)
 * @method bool                                  update(ChallengeModeRunData $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                  delete(ChallengeModeRunData $model)
 * @method Collection<int, ChallengeModeRunData> all()
 * @method bool                                  exists(array<int, string> $columns)
 */
interface ChallengeModeRunDataRepositoryInterface extends BaseRepositoryInterface
{
}
