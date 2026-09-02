<?php

namespace App\Console\Commands\Scheduler\Telemetry;

use App\Console\Commands\Scheduler\SchedulerCommand;
use App\Repositories\Interfaces\Telemetry\TelemetryMetricRepositoryInterface;

class Prune extends SchedulerCommand
{
    protected $signature = 'telemetry:prune';

    protected $description = 'Deletes telemetry metric records older than the configured retention period';

    public function handle(TelemetryMetricRepositoryInterface $telemetryMetricRepository): int
    {
        return $this->trackTime(function () use ($telemetryMetricRepository): void {
            $retentionDays = config('keystoneguru.telemetry.retention_days');
            $cutoff        = now()->subDays($retentionDays);
            $batchSize     = 10000;
            $totalDeleted  = 0;

            do {
                $deleted = $telemetryMetricRepository->deleteRecordedBefore($cutoff, $batchSize);

                $totalDeleted += $deleted;
            } while ($deleted === $batchSize);

            $this->info(sprintf('Pruned %d telemetry metric records older than %d days.', $totalDeleted, $retentionDays));
        });
    }
}
