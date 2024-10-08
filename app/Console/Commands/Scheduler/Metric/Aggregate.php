<?php

namespace App\Console\Commands\Scheduler\Metric;

use App\Console\Commands\Scheduler\SchedulerCommand;
use App\Service\Metric\MetricServiceInterface;

/**
 * Class Aggregate
 *
 * @author Wouter
 *
 * @since 16/02/2023
 */
class Aggregate extends SchedulerCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'metric:aggregate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aggregates all metrics and writes them to the metric aggregations table';

    /**
     * Execute the console command.
     */
    public function handle(MetricServiceInterface $metricService): int
    {
        return $this->trackTime(function () use ($metricService) {
            if ($metricService->aggregateMetrics()) {
                $this->info('Successfully aggregated metrics');

                return 0;
            } else {
                $this->error('Failed to aggregate metrics');

                return 1;
            }
        });
    }
}
