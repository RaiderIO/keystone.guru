<?php

namespace App\Console\Commands\Scheduler\Telemetry\Measurement;


use InfluxDB\Exception;

abstract class Measurement
{
    /**
     * @return array
     * @throws Exception
     */
    abstract function getPoints(): array;

    protected function getTags()
    {
        return ['environment' => env('APP_ENV')];
    }
}
