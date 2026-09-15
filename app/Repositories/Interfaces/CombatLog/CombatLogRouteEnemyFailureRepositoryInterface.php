<?php

namespace App\Repositories\Interfaces\CombatLog;

use App\Models\CombatLog\CombatLogRouteEnemyFailure;
use App\Models\Dungeon;
use App\Repositories\BaseRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * @method CombatLogRouteEnemyFailure                  create(array<string, mixed> $attributes)
 * @method CombatLogRouteEnemyFailure|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method CombatLogRouteEnemyFailure                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method CombatLogRouteEnemyFailure                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                        save(CombatLogRouteEnemyFailure $model)
 * @method bool                                        update(CombatLogRouteEnemyFailure $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                        delete(CombatLogRouteEnemyFailure $model)
 * @method Collection<int, CombatLogRouteEnemyFailure> all()
 * @method bool                                        exists(array<int, string> $columns)
 */
interface CombatLogRouteEnemyFailureRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * A page of the dungeon's failures in ascending id order, starting after $afterId. Fetches one row MORE than
     * $limit so the caller can tell whether another page exists (pop it before handing the page to anyone).
     *
     * @param  int[]|null                                  $npcIds
     * @return Collection<int, CombatLogRouteEnemyFailure>
     */
    public function getPageAfterId(
        Dungeon          $dungeon,
        int              $afterId,
        int              $limit,
        ?int             $mappingVersionId = null,
        ?array           $npcIds = null,
        ?CarbonInterface $since = null,
    ): Collection;
}
