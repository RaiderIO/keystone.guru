<?php

namespace App\Console\Commands\Scheduler\Telemetry\Measurement;

use InfluxDB\Exception;

abstract class Measurement
{
    /**
     * @throws Exception
     */
    abstract public function getPoints(): array;

    protected function getTags()
    {
        return config('keystoneguru.influxdb.default_tags');
    }
}
