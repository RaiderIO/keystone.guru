<?php

namespace App\Console\Commands\Scheduler\Telemetry\Measurements;


use App\Models\Team;
use InfluxDB\Point;

class TeamCount extends Measurement
{
    /**
     * @inheritDoc
     */
    function getPoints(): array
    {
        return [
            new Point(
                'team_count',
                null,
                $this->getTags(),
                [
                    'all' => Team::count()
                ],
                time()
            )
        ];
    }
}
