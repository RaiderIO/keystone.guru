<?php

namespace App\Repositories\Interfaces\CombatLog;

use App\Models\CombatLog\CombatLogRouteEnemyResolution;
use App\Repositories\BaseRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * @method CombatLogRouteEnemyResolution                  create(array<string, mixed> $attributes)
 * @method CombatLogRouteEnemyResolution|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method CombatLogRouteEnemyResolution                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method CombatLogRouteEnemyResolution                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                           save(CombatLogRouteEnemyResolution $model)
 * @method bool                                           update(CombatLogRouteEnemyResolution $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                           delete(CombatLogRouteEnemyResolution $model)
 * @method Collection<int, CombatLogRouteEnemyResolution> all()
 * @method bool                                           exists(array<int, string> $columns)
 */
interface CombatLogRouteEnemyResolutionRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Deletes the resolutions recorded before $cutoff, in batches, and answers how many rows went.
     */
    public function deleteOlderThan(CarbonInterface $cutoff, int $batchSize): int;
}
