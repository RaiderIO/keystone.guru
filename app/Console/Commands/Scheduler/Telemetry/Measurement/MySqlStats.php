<?php

namespace App\Console\Commands\Scheduler\Telemetry\Measurement;


use DB;
use InfluxDB\Point;

class MySqlStats extends Measurement
{
    /**
     * @inheritDoc
     */
    function getPoints(): array
    {
        $threadsConnected = DB::select('SHOW STATUS WHERE `variable_name` = "Threads_connected"')[0]->Value;
        $maxThreads       = DB::select('SHOW VARIABLES LIKE "max_connections"')[0]->Value;

        return [
            new Point(
                'threads_connected',
                null,
                $this->getTags(),
                [
                    'current' => $threadsConnected,
                    'max'     => $maxThreads,
                ],
                time()
            ),
        ];
    }
}
