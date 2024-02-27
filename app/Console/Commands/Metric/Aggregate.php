<?php

namespace App\Console\Commands\Metric;

use App\Service\Metric\MetricServiceInterface;
use Illuminate\Console\Command;

/**
 * Class Aggregate
 *
 * @author Wouter
 *
 * @since 16/02/2023
 */
class Aggregate extends Command
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
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(MetricServiceInterface $metricService): int
    {
        if ($metricService->aggregateMetrics()) {
            $this->info('Successfully aggregated metrics');

            return 0;
        } else {
            $this->error('Failed to aggregate metrics');

            return 1;
        }
    }
}
