<?php

namespace App\Console\Commands\Scheduler\Telemetry\Measurement;

use DB;
use InfluxDB\Point;

class MySqlStats extends Measurement
{
    /**
     * {@inheritDoc}
     */
    public function getPoints(): array
    {
        $threadsConnected = (int)DB::select('SHOW STATUS WHERE `variable_name` = "Threads_connected"')[0]->Value;
        $maxThreads       = (int)DB::select('SHOW VARIABLES LIKE "max_connections"')[0]->Value;

        return [
            new Point(
                'mysql_threads_connected',
                null,
                $this->getTags(),
                [
                    'current' => $threadsConnected,
                    'max'     => $maxThreads,
                ],
                time(),
            ),
        ];
    }
}
