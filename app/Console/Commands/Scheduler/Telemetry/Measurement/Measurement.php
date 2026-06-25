<?php

namespace App\Console\Commands\Scheduler\Telemetry\Measurement;

use InfluxDB\Exception;

abstract class Measurement
{
    /**
     * @throws Exception
     * @return array<int, mixed>
     */
    abstract public function getPoints(): array;

    /**
     * @return array<string, string>
     */
    protected function getTags()
    {
        return config('keystoneguru.influxdb.default_tags');
    }
}
