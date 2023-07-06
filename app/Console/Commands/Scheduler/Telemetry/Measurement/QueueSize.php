<?php

namespace App\Console\Commands\Scheduler\Telemetry\Measurement;


use Illuminate\Support\Facades\Queue;
use InfluxDB\Point;

class QueueSize extends Measurement
{
    /**
     * @inheritDoc
     */
    function getPoints(): array
    {
        $tags = array_merge($this->getTags(), ['server' => 'maisie']);

        $result = [];
        foreach (config(sprintf('horizon.environments.%s', config('app.env'))) as $key => $config) {
            $queueName = $config['queue'][0];

            $result[] = new Point(
                'queue',
                null,
                array_merge($tags, ['name' => $queueName]),
                [
                    'size' => Queue::size($queueName),
                ],
                time()
            );
        }

        return $result;
    }
}
