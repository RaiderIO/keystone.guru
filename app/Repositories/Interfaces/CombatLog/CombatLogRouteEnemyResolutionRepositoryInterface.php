<?php

namespace App\Repositories\Interfaces\CombatLog;

use App\Models\CombatLog\CombatLogRouteEnemyResolution;
use App\Models\Dungeon;
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

    /**
     * A page of the dungeon's resolutions in ascending id order, starting after $afterId. Fetches one row MORE than
     * $limit so the caller can tell whether another page exists (pop it before handing the page to anyone).
     *
     * @param  int[]|null                                     $npcIds
     * @return Collection<int, CombatLogRouteEnemyResolution>
     */
    public function getPageAfterId(
        Dungeon          $dungeon,
        int              $afterId,
        int              $limit,
        ?int             $mappingVersionId = null,
        ?array           $npcIds = null,
        ?CarbonInterface $since = null,
        ?float           $minDistance = null,
    ): Collection;
}
