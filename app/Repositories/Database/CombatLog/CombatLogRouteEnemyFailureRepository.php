<?php

namespace App\Repositories\Database\CombatLog;

use App\Models\CombatLog\CombatLogRouteEnemyFailure;
use App\Models\Dungeon;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\CombatLog\CombatLogRouteEnemyFailureRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CombatLogRouteEnemyFailureRepository extends DatabaseRepository implements CombatLogRouteEnemyFailureRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(CombatLogRouteEnemyFailure::class);
    }

    public function getPageAfterId(
        Dungeon          $dungeon,
        int              $afterId,
        int              $limit,
        ?int             $mappingVersionId = null,
        ?array           $npcIds = null,
        ?CarbonInterface $since = null,
    ): Collection {
        /** @var Collection<int, CombatLogRouteEnemyFailure> $result */
        $result = CombatLogRouteEnemyFailure::query()
            ->where('dungeon_id', $dungeon->id)
            ->where('id', '>', $afterId)
            ->when($mappingVersionId !== null, static fn(Builder $builder) => $builder->where('mapping_version_id', $mappingVersionId))
            ->when(!empty($npcIds), static fn(Builder $builder) => $builder->whereIn('npc_id', $npcIds))
            ->when($since !== null, static fn(Builder $builder) => $builder->where('created_at', '>=', $since))
            ->orderBy('id')
            ->limit($limit + 1)
            ->get();

        return $result;
    }
}
