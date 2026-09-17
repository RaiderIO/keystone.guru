<?php

namespace App\Repositories\Database\CombatLog;

use App\Models\CombatLog\CombatLogRouteEnemyResolution;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\CombatLog\CombatLogRouteEnemyResolutionRepositoryInterface;
use Carbon\CarbonInterface;
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
}
