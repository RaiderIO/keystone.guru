<?php

namespace App\Console\Commands\Scheduler\Telemetry;

use App\Console\Commands\Scheduler\SchedulerCommand;
use App\Repositories\Interfaces\Telemetry\TelemetryMetricRepositoryInterface;

class Prune extends SchedulerCommand
{
    protected $signature = 'telemetry:prune';

    protected $description = 'Deletes operational telemetry metric records older than the configured retention period, keeping growth measurements forever';

    public function handle(TelemetryMetricRepositoryInterface $telemetryMetricRepository): int
    {
        return $this->trackTime(function () use ($telemetryMetricRepository): void {
            $retentionDays      = config('keystoneguru.telemetry.retention_days');
            $growthMeasurements = config('keystoneguru.telemetry.growth_measurements');
            $cutoff             = now()->subDays($retentionDays);
            $batchSize          = 10000;
            $totalDeleted       = 0;

            do {
                $deleted = $telemetryMetricRepository->deleteRecordedBefore($cutoff, $batchSize, $growthMeasurements);

                $totalDeleted += $deleted;
            } while ($deleted === $batchSize);

            $this->info(sprintf(
                'Pruned %d telemetry metric records older than %d days (excluding growth measurements: %s).',
                $totalDeleted,
                $retentionDays,
                implode(', ', $growthMeasurements),
            ));
        });
    }
}
