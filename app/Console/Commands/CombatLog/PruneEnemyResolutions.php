<?php

namespace App\Console\Commands\CombatLog;

use App\Console\Commands\Scheduler\SchedulerCommand;
use App\Repositories\Interfaces\CombatLog\CombatLogRouteEnemyResolutionRepositoryInterface;

/**
 * Keeps combat_log_route_enemy_resolutions bounded. Every ingested route contributes a handful of rows, and a match
 * that was too far off months ago says nothing about how the dungeon is mapped today.
 */
class PruneEnemyResolutions extends SchedulerCommand
{
    private const int DELETE_BATCH_SIZE = 10000;

    protected $signature = 'combatlog:pruneenemyresolutions';

    protected $description = 'Deletes combat_log_route_enemy_resolutions rows older than the configured retention window.';

    public function handle(CombatLogRouteEnemyResolutionRepositoryInterface $combatLogRouteEnemyResolutionRepository): int
    {
        return $this->trackTime(function () use ($combatLogRouteEnemyResolutionRepository): void {
            $retentionDays = (int)config('keystoneguru.enemy_resolution.retention_days');

            $totalDeleted = $combatLogRouteEnemyResolutionRepository->deleteOlderThan(
                now()->subDays($retentionDays),
                self::DELETE_BATCH_SIZE,
            );

            $this->info(sprintf('Pruned %d combat_log_route_enemy_resolutions records older than %d days.', $totalDeleted, $retentionDays));
        });
    }
}
