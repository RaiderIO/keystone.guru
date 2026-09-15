<?php

namespace App\Console\Commands\Scheduler\Telemetry\Measurement;

use App\Models\Telemetry\TelemetryMetric;
use App\Service\Telemetry\Dtos\TelemetryDataPoint;
use Illuminate\Support\Facades\Queue;

class QueueSize extends Measurement
{
    /**
     * {@inheritDoc}
     */
    public function getDataPoints(): array
    {
        $result = [];
        foreach (config(sprintf('horizon.environments.%s', config('app.env'))) as $key => $config) {
            $queueName = $config['queue'][0];

            $result[] = new TelemetryDataPoint(
                TelemetryMetric::MEASUREMENT_QUEUE,
                'size',
                Queue::size($queueName),
                $queueName,
            );
        }

        return $result;
    }
}
