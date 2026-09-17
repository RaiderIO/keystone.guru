<?php

namespace App\Repositories\Database\CombatLog;

use App\Models\CombatLog\CombatLogRouteEnemyResolution;
use App\Models\Dungeon;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\CombatLog\CombatLogRouteEnemyResolutionRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CombatLogRouteEnemyResolutionRepository extends DatabaseRepository implements CombatLogRouteEnemyResolutionRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(CombatLogRouteEnemyResolution::class);
    }

    public function deleteOlderThan(CarbonInterface $cutoff, int $batchSize): int
    {
        $totalDeleted = 0;

        CombatLogRouteEnemyResolution::query()
            ->select(['id'])
            ->where('created_at', '<', $cutoff)
            ->chunkById($batchSize, function (Collection $resolutions) use (&$totalDeleted): void {
                $totalDeleted += CombatLogRouteEnemyResolution::query()
                    ->whereIn('id', $resolutions->pluck('id'))
                    ->delete();
            });

        return $totalDeleted;
    }

    public function getPageAfterId(
        Dungeon          $dungeon,
        int              $afterId,
        int              $limit,
        ?int             $mappingVersionId = null,
        ?array           $npcIds = null,
        ?CarbonInterface $since = null,
        ?float           $minDistance = null,
    ): Collection {
        /** @var Collection<int, CombatLogRouteEnemyResolution> $result */
        $result = CombatLogRouteEnemyResolution::query()
            ->where('dungeon_id', $dungeon->id)
            ->where('id', '>', $afterId)
            ->when($mappingVersionId !== null, static fn(Builder $builder) => $builder->where('mapping_version_id', $mappingVersionId))
            ->when(!empty($npcIds), static fn(Builder $builder) => $builder->whereIn('npc_id', $npcIds))
            ->when($since !== null, static fn(Builder $builder) => $builder->where('created_at', '>=', $since))
            ->when($minDistance !== null, static fn(Builder $builder) => $builder->where('weighted_distance', '>=', $minDistance))
            ->orderBy('id')
            ->limit($limit + 1)
            ->get();

        return $result;
    }
}
