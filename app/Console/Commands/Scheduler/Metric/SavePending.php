<?php

namespace App\Console\Commands\Scheduler\Metric;

use App\Console\Commands\Scheduler\SchedulerCommand;
use App\Logic\Utils\Stopwatch;
use App\Models\Metrics\Metric;
use App\Service\Metric\MetricServiceInterface;
use Illuminate\Support\Facades\Log;

/**
 * Class SavePending
 *
 * @author Wouter
 *
 * @since 05/03/2025
 */
class SavePending extends SchedulerCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'metric:savepending';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Flushes and stores any pending metrics to the database.';

    /**
     * Execute the console command.
     */
    public function handle(MetricServiceInterface $metricService): int
    {
        $pendingMetrics = $metricService->flushPendingMetrics(60);

        if (empty($pendingMetrics)) {
            Log::channel('scheduler')->info('No pending metrics to save.');

            return 0;
        }

        Log::channel('scheduler')->info(sprintf('Flushing %d pending metrics to the database.', count($pendingMetrics)));
        Stopwatch::start('metric:savepending');
        $result = Metric::insert($pendingMetrics);
        if ($result) {
            Log::channel('scheduler')->info(
                sprintf('Successfully saved metrics to the database in %d ms.', Stopwatch::stop('metric:savepending'))
            );
        } else {
            Log::channel('scheduler')->error('Failed to save metrics to the database.');
        }

        return !$result;
    }
}
