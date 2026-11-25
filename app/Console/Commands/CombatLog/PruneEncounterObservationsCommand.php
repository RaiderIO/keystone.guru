<?php

namespace App\Console\Commands\CombatLog;

use App\Console\Commands\Scheduler\SchedulerCommand;
use App\Repositories\Interfaces\CombatLog\CombatLogEncounterObservationRepositoryInterface;

class PruneEncounterObservationsCommand extends SchedulerCommand
{
    private const int BATCH_SIZE = 10000;

    protected $signature = 'combatlog:pruneencounterobservations';

    protected $description = 'Deletes combat_log_encounter_observations rows older than the configured retention.';

    public function handle(CombatLogEncounterObservationRepositoryInterface $combatLogEncounterObservationRepository): int
    {
        return $this->trackTime(function () use ($combatLogEncounterObservationRepository): void {
            $retentionDays = (int)config('keystoneguru.combat_log_encounter_observations.retention_days');
            $cutoff        = now()->subDays($retentionDays);
            $totalDeleted  = 0;

            do {
                $deleted = $combatLogEncounterObservationRepository->deleteCreatedBefore($cutoff, self::BATCH_SIZE);
                $totalDeleted += $deleted;
            } while ($deleted === self::BATCH_SIZE);

            $this->info(sprintf('Pruned %d combat_log_encounter_observations records older than %d days.', $totalDeleted, $retentionDays));
        });
    }
}
