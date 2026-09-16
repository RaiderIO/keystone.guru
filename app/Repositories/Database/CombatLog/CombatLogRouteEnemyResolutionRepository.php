<?php

namespace App\Repositories\Database\CombatLog;

use App\Models\CombatLog\CombatLogRouteEnemyResolution;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\CombatLog\CombatLogRouteEnemyResolutionRepositoryInterface;
use Carbon\CarbonInterface;

class CombatLogRouteEnemyResolutionRepository extends DatabaseRepository implements CombatLogRouteEnemyResolutionRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(CombatLogRouteEnemyResolution::class);
    }

    public function deleteOlderThan(CarbonInterface $cutoff, int $batchSize): int
    {
        $totalDeleted = 0;

        do {
            $deleted = CombatLogRouteEnemyResolution::query()
                ->where('created_at', '<', $cutoff)
                ->limit($batchSize)
                ->delete();

            $totalDeleted += $deleted;
        } while ($deleted === $batchSize);

        return $totalDeleted;
    }
}
