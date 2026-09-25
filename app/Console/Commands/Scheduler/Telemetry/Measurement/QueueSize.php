<?php

namespace App\Console\Commands\Scheduler\Telemetry\Measurement;

use App\Jobs\Enums\QueueName;
use App\Models\Telemetry\TelemetryMetric;
use App\Service\Telemetry\Dtos\TelemetryDataPoint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Throwable;

class QueueSize extends Measurement
{
    /**
     * {@inheritDoc}
     */
    public function getDataPoints(): array
    {
        $result = [];
        foreach (QueueName::cases() as $queue) {
            $queueName = $queue->queueName();

            try {
                // SQS throws for a queue that was never created instead of reporting 0
                $size = Queue::size($queueName);
            } catch (Throwable $exception) {
                Log::warning(sprintf('Unable to measure the size of queue %s: %s', $queueName, $exception->getMessage()));

                continue;
            }

            $result[] = new TelemetryDataPoint(
                TelemetryMetric::MEASUREMENT_QUEUE,
                'size',
                $size,
                $queueName,
            );
        }

        return $result;
    }
}
